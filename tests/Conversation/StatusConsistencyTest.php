<?php
declare(strict_types=1);

namespace App\Tests\Conversation;

use App\Conversation\Consistency\StatusConsistency;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\JsonException;

/**
 * @package App\Tests\Conversation
 * @group   conversation
 */
class StatusConsistencyTest extends TestCase
{
    /**
     * @var string
     */
    private string $originalDocument;

    /**
     * @var array
     */
    private $status;

    /**
     * @test
     * @throws JsonException
     */
    public function it_should_fill_missing_props_of_a_status()
    {
        $actualStatus = StatusConsistency::fillMissingStatusProps(
            $this->originalDocument,
            $this->status
        );

        $this->assertEquals(
            [
                'status_id' => '1226376436388376576',
                'text'      => 'Avec 811 morts en Chine, le nouveau coronavirus devient plus meurtrier que le SRAS https://t.co/NovMuwncTY'
            ],
            $actualStatus,
            );
    }

    protected function setUp(): void
    {
        $this->originalDocument = <<< DOC
{"created_at":"Sun Feb 09 05:25:02 +0000 2020","id":1226376436388376576,"id_str":"1226376436388376576","full_text":"Avec 811 morts en Chine, le nouveau coronavirus devient plus meurtrier que le SRAS https:\/\/t.co\/NovMuwncTY","truncated":false,"display_text_range":[0,106],"entities":{"hashtags":[],"symbols":[],"user_mentions":[],"urls":[{"url":"https:\/\/t.co\/NovMuwncTY","expanded_url":"https:\/\/www.lemonde.fr\/planete\/article\/2020\/02\/09\/avec-811-morts-en-chine-le-nouveau-coronavirus-devient-plus-meurtrier-que-le-sras_6028944_3244.html?utm_term=Autofeed&utm_medium=Social&utm_source=Twitter#Echobox=1581212250","display_url":"lemonde.fr\/planete\/articl\u2026","indices":[83,106]}]},"source":"<a href=\"https:\/\/www.echobox.com\" rel=\"nofollow\">Echobox Social<\/a>","in_reply_to_status_id":null,"in_reply_to_status_id_str":null,"in_reply_to_user_id":null,"in_reply_to_user_id_str":null,"in_reply_to_screen_name":null,"user":{"id":24744541,"id_str":"24744541","name":"Le Monde","screen_name":"lemondefr","location":"Paris","description":"L'actualit\u00e9 de r\u00e9f\u00e9rence par la r\u00e9daction du Monde | Pilot\u00e9 par @marieslavicek @bricelaemle @charlotteherzog | Snapchat, FB, Instagram : lemondefr","url":"https:\/\/t.co\/er70UGkbir","entities":{"url":{"urls":[{"url":"https:\/\/t.co\/er70UGkbir","expanded_url":"https:\/\/www.lemonde.fr","display_url":"lemonde.fr","indices":[0,23]}]},"description":{"urls":[]}},"protected":false,"followers_count":8578482,"friends_count":626,"listed_count":35973,"created_at":"Mon Mar 16 18:44:51 +0000 2009","favourites_count":1619,"utc_offset":null,"time_zone":null,"geo_enabled":false,"verified":true,"statuses_count":322618,"lang":null,"contributors_enabled":false,"is_translator":false,"is_translation_enabled":true,"profile_background_color":"DDE1EA","profile_background_image_url":"http:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png","profile_background_image_url_https":"https:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png","profile_background_tile":true,"profile_image_url":"http:\/\/pbs.twimg.com\/profile_images\/817042499134980096\/LTpqSDMM_normal.jpg","profile_image_url_https":"https:\/\/pbs.twimg.com\/profile_images\/817042499134980096\/LTpqSDMM_normal.jpg","profile_banner_url":"https:\/\/pbs.twimg.com\/profile_banners\/24744541\/1491832878","profile_link_color":"50B6CF","profile_sidebar_border_color":"131316","profile_sidebar_fill_color":"131316","profile_text_color":"3292A8","profile_use_background_image":true,"has_extended_profile":false,"default_profile":false,"default_profile_image":false,"following":false,"follow_request_sent":false,"notifications":false,"translator_type":"none"},"geo":null,"coordinates":null,"place":null,"contributors":null,"is_quote_status":false,"retweet_count":53,"favorite_count":52,"favorited":false,"retweeted":false,"possibly_sensitive":false,"lang":"fr"}
DOC;

        $this->status = [
        ];
    }
}