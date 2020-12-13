<?php
declare(strict_types=1);

namespace App\Conversation;

use App\Conversation\Consistency\StatusConsistency;
use App\Conversation\Exception\InvalidStatusException;
use App\Conversation\Validation\StatusValidator;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\StatusAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Domain\Publication\Repository\PublicationInterface;
use function array_key_exists;
use function json_decode;

trait ConversationAwareTrait
{
    use StatusAccessorTrait;
    use StatusRepositoryTrait;

    /**
     * @param array $status
     * @param array $decodedDocument
     * @param bool  $includeRepliedToStatuses
     *
     * @return array
     */
    public function updateFromDecodedDocument(
        array $status,
        array $decodedDocument,
        bool $includeRepliedToStatuses = false
    ): array {
        $status['media'] = [];
        if (
            array_key_exists('entities', $decodedDocument)
            && array_key_exists('media', $decodedDocument['entities'])
        ) {
            $status['media'] = array_map(
                function ($media) {
                    if (array_key_exists('media_url_https', $media)) {
                        return [
                            'sizes' => $media['sizes'],
                            'url'   => $media['media_url_https'],
                        ];
                    }
                },
                $decodedDocument['entities']['media']
            );
        }

        if (array_key_exists('avatar_url', $decodedDocument)) {
            $status['avatar_url'] = $decodedDocument['avatar_url'];
        }

        if (
            array_key_exists('user', $decodedDocument)
            && array_key_exists('profile_image_url_https', $decodedDocument['user'])
        ) {
            $status['avatar_url'] = $decodedDocument['user']['profile_image_url_https'];
        }

        if (array_key_exists('retweet_count', $decodedDocument)) {
            $status['retweet_count'] = $decodedDocument['retweet_count'];
        }

        if (array_key_exists('favorite_count', $decodedDocument)) {
            $status['favorite_count'] = $decodedDocument['favorite_count'];
        }

        if (array_key_exists('created_at', $decodedDocument)) {
            $status['published_at'] = $decodedDocument['created_at'];
        }

        return $this->extractConversationProperties(
            $status,
            $decodedDocument,
            $includeRepliedToStatuses
        );
    }

    /**
     * @param array $updatedStatus
     * @param array $decodedDocument
     * @param bool  $includeRepliedToStatuses
     *
     * @return array
     */
    private function extractConversationProperties(
        array $updatedStatus,
        array $decodedDocument,
        bool $includeRepliedToStatuses = false
    ): array {
        $updatedStatus['in_conversation'] = null;
        if (
            $includeRepliedToStatuses && array_key_exists('in_reply_to_status_id_str', $decodedDocument)
            && $decodedDocument['in_reply_to_status_id_str'] !== null
        ) {
            $updatedStatus['id_of_status_replied_to']       = $decodedDocument['in_reply_to_status_id_str'];
            $updatedStatus['username_of_member_replied_to'] = $decodedDocument['in_reply_to_screen_name'];
            $updatedStatus['in_conversation']               = true;

            try {
                $repliedToStatus = $this->statusAccessor->refreshStatusByIdentifier(
                    $updatedStatus['id_of_status_replied_to']
                );
            } catch (NotFoundMemberException $notFoundMemberException) {
                $this->statusAccessor->ensureMemberHavingNameExists($notFoundMemberException->screenName);
                $repliedToStatus = $this->statusAccessor->refreshStatusByIdentifier(
                    $updatedStatus['id_of_status_replied_to']
                );
            }

            $repliedToStatus                    =
                $this->extractStatusProperties([$repliedToStatus], $includeRepliedToStatuses = true);
            $updatedStatus['status_replied_to'] = $repliedToStatus[0];
        }

        return $updatedStatus;
    }

    /**
     * @param array $statuses
     * @param bool  $includeRepliedToStatuses
     *
     * @return array
     */
    private function extractStatusProperties(
        array $statuses,
        bool $includeRepliedToStatuses = false
    ): array {
        return array_map(
            function ($status) use ($includeRepliedToStatuses) {
                if ($status instanceof StatusInterface) {
                    $status = [
                        'screen_name'       => $status->getScreenName(),
                        'status_id'         => $status->getStatusId(),
                        'text'              => $status->getText(),
                        'original_document' => $status->getApiDocument(),
                    ];
                }

                if ($status instanceof PublicationInterface) {
                    $status = [
                        'screen_name'       => $status->getScreenName(),
                        'status_id'         => $status->getDocumentId(),
                        'text'              => $status->getText(),
                        'original_document' => $status->getDocument(),
                    ];
                }

                try {
                    StatusValidator::guardAgainstMissingOriginalDocument($status);
                    StatusValidator::guardAgainstMissingStatusId($status);
                    StatusValidator::guardAgainstMissingText($status);
                } catch (InvalidStatusException $exception) {
                    if ($exception->wasThrownBecauseOfMissingOriginalDocument()) {
                        throw $exception;
                    }

                    $status = StatusConsistency::fillMissingStatusProps(
                        $status['original_document'],
                        $status
                    );
                }

                $defaultStatus = [
                    'status_id'      => $status['status_id'],
                    'avatar_url'     => 'N/A',
                    'text'           => $status['text'],
                    'url'            => 'https://twitter.com/'
                        . $status['screen_name']
                        . '/status/'
                        . $status['status_id'],
                    'retweet_count'  => 'N/A',
                    'favorite_count' => 'N/A',
                    'username'       => $status['screen_name'],
                    'published_at'   => 'N/A',
                ];

                $hasDocumentFromApi = array_key_exists('api_document', $status);

                if (
                    !array_key_exists('original_document', $status)
                    && !$hasDocumentFromApi
                ) {
                    return $defaultStatus;
                }

                if ($hasDocumentFromApi) {
                    $status['original_document'] = $status['api_document'];
                    unset($status['api_document']);
                }

                $decodedDocument = json_decode($status['original_document'], $asAssociativeArray = true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $defaultStatus;
                }

                if ($defaultStatus['status_id'] === null) {
                    $defaultStatus['url'] =
                        'https://twitter.com/' . $status['screen_name'] . '/status/' . $decodedDocument['id_str'];
                }

                if ($defaultStatus['status_id'] === null) {
                    $defaultStatus['status_id'] = $decodedDocument['id_str'];
                }

                if (
                    array_key_exists('full_text', $decodedDocument)
                    && $defaultStatus['text'] !== $decodedDocument['full_text']
                ) {
                    $defaultStatus['text'] = $decodedDocument['full_text'];
                }

                if (
                    !array_key_exists('full_text', $decodedDocument)
                    && array_key_exists('text', $decodedDocument)
                ) {
                    $defaultStatus['text'] = $decodedDocument['text'];
                }

                $likedBy = null;
                if (array_key_exists('liked_by', $status)) {
                    $likedBy = $status['liked_by'];
                }

                if (array_key_exists('retweeted_status', $decodedDocument)) {
                    $updatedStatus                                  = $this->updateFromDecodedDocument(
                        $defaultStatus,
                        $decodedDocument['retweeted_status'],
                        $includeRepliedToStatuses
                    );
                    $updatedStatus['username']                      =
                        $decodedDocument['retweeted_status']['user']['screen_name'];
                    $updatedStatus['username_of_retweeting_member'] = $defaultStatus['username'];
                    $updatedStatus['retweet']                       = true;
                    $updatedStatus['text']                          = $decodedDocument['retweeted_status']['full_text'];
                    if (!is_null($likedBy)) {
                        $updatedStatus['liked_by'] = $likedBy;
                    }

                    return $updatedStatus;
                }

                $statusUpdatedFromDecodedDocument = $defaultStatus;
                $updatedStatus                    = $this->updateFromDecodedDocument(
                    $statusUpdatedFromDecodedDocument,
                    $decodedDocument,
                    $includeRepliedToStatuses
                );
                $updatedStatus['retweet']         = false;
                if ($likedBy !== null) {
                    $updatedStatus['liked_by'] = $likedBy;
                }

                return $updatedStatus;
            },
            $statuses
        );
    }

    private function findStatusOrFetchItByIdentifier($statusId, $shouldRefreshStatus = false)
    {
        if ($shouldRefreshStatus) {
            return $this->statusAccessor->refreshStatusByIdentifier($statusId, $skipExistingStatus = true);
        }

        return $this->statusRepository->findStatusIdentifiedBy($statusId);
    }
}
