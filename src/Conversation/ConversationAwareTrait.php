<?php
declare(strict_types=1);

namespace App\Conversation;

use App\Conversation\Consistency\StatusConsistency;
use App\Conversation\Exception\InvalidStatusException;
use App\Conversation\Validation\StatusValidator;
use App\Media\Image;
use App\Media\ImageProcessingException;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\StatusAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Domain\Publication\Repository\PublicationInterface;
use Exception;
use GdImage;
use function array_key_exists;
use function Exception;
use function json_decode;

trait ConversationAwareTrait
{
    use StatusAccessorTrait;
    use StatusRepositoryTrait;

    private const DEFAULT_PROFILE_PICTURE_1X = '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YjdmLWFmYjUtYWExOWQwYzk5ODcyIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjRlNzdhNTBiLWQzNzUtNDljMS04ZmQxLTYyNDUwZDA4ZDZjOCIgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOmVkYjE5N2JiLTM4MmYtNDg1Zi1hYjM2LTViYjkyODViNzA4YSIgZGM6Rm9ybWF0PSJpbWFnZS93ZWJwIiBHSU1QOkFQST0iMi4wIiBHSU1QOlBsYXRmb3JtPSJMaW51eCIgR0lNUDpUaW1lU3RhbXA9IjE2NzgyOTk4Njg0Njc3NTUiIEdJTVA6VmVyc2lvbj0iMi4xMC4zMiIgdGlmZjpPcmllbnRhdGlvbj0iMSIgeG1wOkNyZWF0b3JUb29sPSJHSU1QIDIuMTAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMjM6MDM6MDhUMTk6MjQ6MjgrMDE6MDAiIHhtcDpNb2RpZnlEYXRlPSIyMDIzOjAzOjA4VDE5OjI0OjI4KzAxOjAwIj4gPHhtcE1NOkhpc3Rvcnk+IDxyZGY6U2VxPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiIHN0RXZ0OmNoYW5nZWQ9Ii8iIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6ZjlkMmZhZDctM2VlMy00NzAzLWE0YjMtMDc5ZTUwMTRjN2ViIiBzdEV2dDpzb2Z0d2FyZUFnZW50PSJHaW1wIDIuMTAgKExpbnV4KSIgc3RFdnQ6d2hlbj0iMjAyMy0wMy0wOFQxOToxNzo1MyswMTowMCIvPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiIHN0RXZ0OmNoYW5nZWQ9Ii8iIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6M2IxY2EyMmMtZWY1NS00ZDY2LTk3N2QtNTQ3YjgxNWI2NGU4IiBzdEV2dDpzb2Z0d2FyZUFnZW50PSJHaW1wIDIuMTAgKExpbnV4KSIgc3RFdnQ6d2hlbj0iMjAyMy0wMy0wOFQxOToyNDoyOCswMTowMCIvPiA8L3JkZjpTZXE+IDwveG1wTU06SGlzdG9yeT4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD94cGFja2V0IGVuZD0idyI/Pg==';
    private const DEFAULT_PROFILE_PICTURE_2X = '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dHRwOi8vd3d3LmdpbXAub3JnL3htcC8iIHhtbG5zOnRpZmY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vdGlmZi8xLjAvIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9ImdpbXA6ZG9jaWQ6Z2ltcDo0MzNlMGQ3NS1lNjhkLTQ5NmYtOGRlMS0zZGY5ZjU5ZWZiNGMiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6ODM1OWQ4MDAtNGI4Zi00Y2Q2LWIwMjktNmI5YzkyZmEyODVkIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6ZDU2NDNlZmMtODJmZi00NDk0LThkN2EtN2RmOTlmNmNlM2FlIiBkYzpGb3JtYXQ9ImltYWdlL3dlYnAiIEdJTVA6QVBJPSIyLjAiIEdJTVA6UGxhdGZvcm09IkxpbnV4IiBHSU1QOlRpbWVTdGFtcD0iMTY3ODI5OTg0Mzg5OTg0OSIgR0lNUDpWZXJzaW9uPSIyLjEwLjMyIiB0aWZmOk9yaWVudGF0aW9uPSIxIiB4bXA6Q3JlYXRvclRvb2w9IkdJTVAgMi4xMCIgeG1wOk1ldGFkYXRhRGF0ZT0iMjAyMzowMzowOFQxOToyNDowMyswMTowMCIgeG1wOk1vZGlmeURhdGU9IjIwMjM6MDM6MDhUMTk6MjQ6MDMrMDE6MDAiPiA8eG1wTU06SGlzdG9yeT4gPHJkZjpTZXE+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJzYXZlZCIgc3RFdnQ6Y2hhbmdlZD0iLyIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDo2Yjc2MWJiYy1iMjkwLTQ5ODctOGZlNS0xOTA3MmVjYmZhNGMiIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkdpbXAgMi4xMCAoTGludXgpIiBzdEV2dDp3aGVuPSIyMDIzLTAzLTA4VDE5OjI0OjAzKzAxOjAwIi8+IDwvcmRmOlNlcT4gPC94bXBNTTpIaXN0b3J5PiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3hwYWNrZXQgZW5kPSJ3Ij8+';
    private const DEFAULT_PROFILE_PICTURE_3X = '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PiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIiB4bWxuczpHSU1QPSJodHRwOi8vd3d3LmdpbXAub3JnL3htcC8iIHhtbG5zOnRpZmY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vdGlmZi8xLjAvIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9ImdpbXA6ZG9jaWQ6Z2ltcDo0MjMyNDk1Yy1iNmUzLTRkZTctYWQ1OS03ODQ3ZDk3ZjQ4MDYiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MzI3OGJlMmUtMGQzNi00NjUzLWFiNWYtMjI1MDY3MTY3MTBhIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6ODYxYThiNzItMGRiMS00YWVkLThlZjgtMzdlMGYyODUxNTI5IiBkYzpGb3JtYXQ9ImltYWdlL3dlYnAiIEdJTVA6QVBJPSIyLjAiIEdJTVA6UGxhdGZvcm09IkxpbnV4IiBHSU1QOlRpbWVTdGFtcD0iMTY3ODI5OTg2MDMyMzk3MCIgR0lNUDpWZXJzaW9uPSIyLjEwLjMyIiB0aWZmOk9yaWVudGF0aW9uPSIxIiB4bXA6Q3JlYXRvclRvb2w9IkdJTVAgMi4xMCIgeG1wOk1ldGFkYXRhRGF0ZT0iMjAyMzowMzowOFQxOToyNDoxOSswMTowMCIgeG1wOk1vZGlmeURhdGU9IjIwMjM6MDM6MDhUMTk6MjQ6MTkrMDE6MDAiPiA8eG1wTU06SGlzdG9yeT4gPHJkZjpTZXE+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJzYXZlZCIgc3RFdnQ6Y2hhbmdlZD0iLyIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDpkNjQzMGVlYi0xODdmLTQ2OTctOTVjMy0wMWVjNWI3NzA3ZDEiIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkdpbXAgMi4xMCAoTGludXgpIiBzdEV2dDp3aGVuPSIyMDIzLTAzLTA4VDE5OjE4OjMyKzAxOjAwIi8+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJzYXZlZCIgc3RFdnQ6Y2hhbmdlZD0iLyIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDoxODI5NzI1Ny0xNjFjLTQzZTYtYjBjMi0wNDE1NjRkMjJmMGYiIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkdpbXAgMi4xMCAoTGludXgpIiBzdEV2dDp3aGVuPSIyMDIzLTAzLTA4VDE5OjI0OjIwKzAxOjAwIi8+IDwvcmRmOlNlcT4gPC94bXBNTTpIaXN0b3J5PiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3hwYWNrZXQgZW5kPSJ3Ij8+';

    /**
     * @throws InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException|\JsonException
     * @throws \Safe\Exceptions\FilesystemException
     */
    public function updateFromDecodedDocument(
        array $status,
        array $decodedDocument,
        bool  $includeRepliedToStatuses = false
    ): array
    {
        $status['media'] = [];

        $extendedMedia = [];
        if (
            array_key_exists('extended_entities', $decodedDocument)
            && array_key_exists('media', $decodedDocument['extended_entities'])
        ) {
            $extendedMedia = array_map(
                function ($media) {
                    if (isset($media['additional_media_info']['title'])) {
                        return $media['additional_media_info']['title'];
                    }

                    return '';
                },
                $decodedDocument['extended_entities']['media']
            );
        }

        if (
            array_key_exists('entities', $decodedDocument)
            && array_key_exists('media', $decodedDocument['entities'])
        ) {
            $status['media'] = array_map(
                static function ($media, $index) use ($extendedMedia) {
                    if (array_key_exists('media_url_https', $media)) {
                        return [
                            'sizes' => $media['sizes'],
                            'url'   => $media['media_url_https'],
                            'title' => $extendedMedia[$index] ?? $media['type'],
                        ];
                    }
                },
                $decodedDocument['entities']['media'],
                range(0, count($decodedDocument['entities']['media']) - 1)
            );
        }

        $status = $this->addEncodedAvatarToTweetDocument($decodedDocument, $status);

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
     * @throws InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     * @throws \Safe\Exceptions\FilesystemException
     */
    private function extractConversationProperties(
        array $updatedStatus,
        array $decodedDocument,
        bool  $includeRepliedToStatuses = false
    ): array
    {
        $updatedStatus['in_conversation'] = null;
        if (
            $includeRepliedToStatuses && array_key_exists('in_reply_to_status_id_str', $decodedDocument)
            && $decodedDocument['in_reply_to_status_id_str'] !== null
        ) {
            $updatedStatus['id_of_status_replied_to'] = $decodedDocument['in_reply_to_status_id_str'];
            $updatedStatus['username_of_member_replied_to'] = $decodedDocument['in_reply_to_screen_name'];
            $updatedStatus['in_conversation'] = true;

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

            $repliedToStatus = $this->extractTweetProperties([$repliedToStatus], includeRepliedToStatuses: true);
            $updatedStatus['status_replied_to'] = $repliedToStatus[0];
        }

        return $updatedStatus;
    }

    /**
     * @throws InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     * @throws \Safe\Exceptions\FilesystemException
     */
    private function extractTweetProperties(
        array $statuses,
        bool  $includeRepliedToStatuses = false
    ): array
    {
        return array_map(
            function ($tweet) use ($includeRepliedToStatuses) {
                $tweet = $this->convertTweetToArray($tweet);

                if ($this->guardAgainstMissingUpstreamTweetDocument($tweet)) {
                    return $this->getTweetTemplate($tweet);
                }

                try {
                    $decodedDocument = $this->decodeUpstreamDocument($tweet);
                } catch (\JsonException) {
                    return $this->getTweetTemplate($tweet);
                }

                $tweetTemplate = $this->getTweetTemplate($tweet, $decodedDocument);

                $likedBy = null;
                if (array_key_exists('liked_by', $tweet)) {
                    $likedBy = $tweet['liked_by'];
                }

                if (array_key_exists('retweeted_status', $decodedDocument)) {
                    return $this->extractRetweetedStatus(
                        $tweetTemplate,
                        $decodedDocument['retweeted_status'],
                        $includeRepliedToStatuses,
                        $likedBy
                    );
                }

                $updatedStatus = $this->updateFromDecodedDocument(
                    $tweetTemplate,
                    $decodedDocument,
                    $includeRepliedToStatuses
                );

                $updatedStatus['retweet'] = false;

                if ($likedBy !== null) {
                    $updatedStatus['liked_by'] = $likedBy;
                }

                return $updatedStatus;
            },
            $statuses
        );
    }

    /**
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \JsonException
     */
    public function addEncodedAvatarToTweetDocument(array $tweetRawDocument, array $tweet): array
    {
        if (array_key_exists('base64_encoded_avatar', $tweetRawDocument)) {
            $tweet['base64_encoded_avatar'] = $tweetRawDocument['base64_encoded_avatar'];

            return $tweet;
        }

        if (!isset($tweetRawDocument['user']['profile_image_url_https'])) {
            return $tweet;
        }

        $tweet['avatar_url'] = $tweetRawDocument['user']['profile_image_url_https'];

        return $this->getExistingProfilePicturesOrFetchThem($tweet);
    }

    private function extractText($fallbackText, array $decodedDocument): string
    {
        if (array_key_exists('full_text', $decodedDocument) && $fallbackText !== $decodedDocument['full_text']) {
            return $decodedDocument['full_text'];
        } elseif (array_key_exists('text', $decodedDocument)) {
            return $decodedDocument['text'];
        }

        return $fallbackText;
    }

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \Safe\Exceptions\JsonException
     */
    private function convertTweetToArray($tweet): array
    {
        if ($tweet instanceof StatusInterface) {
            $tweet = [
                'screen_name'       => $tweet->getScreenName(),
                'status_id'         => $tweet->getStatusId(),
                'text'              => $tweet->getText(),
                'original_document' => $tweet->getApiDocument(),
            ];
        }

        if ($tweet instanceof PublicationInterface) {
            $tweet = [
                'screen_name'       => $tweet->getScreenName(),
                'status_id'         => $tweet->getDocumentId(),
                'text'              => $tweet->getText(),
                'original_document' => $tweet->getDocument(),
            ];
        }

        try {
            StatusValidator::guardAgainstMissingOriginalDocument($tweet);
            StatusValidator::guardAgainstMissingStatusId($tweet);
            StatusValidator::guardAgainstMissingText($tweet);
        } catch (InvalidStatusException $exception) {
            if ($exception->wasThrownBecauseOfMissingOriginalDocument()) {
                throw $exception;
            }

            $tweet = StatusConsistency::fillMissingStatusProps(
                $tweet['original_document'],
                $tweet
            );
        }

        return $tweet;
    }

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     * @throws \Safe\Exceptions\FilesystemException
     */
    private function extractRetweetedStatus(
        array $tweet,
        array $tweetJSONDocument,
        bool  $includeRepliedToStatuses,
        mixed $likedBy
    ): array
    {
        $retweetedStatus = $this->updateFromDecodedDocument(
            $tweet,
            $tweetJSONDocument,
            $includeRepliedToStatuses
        );
        $retweetedStatus['username'] = $tweetJSONDocument['user']['screen_name'];
        $retweetedStatus['username_of_retweeting_member'] = $tweet['username'];
        $retweetedStatus['retweet'] = true;
        $retweetedStatus['text'] = $tweetJSONDocument['full_text'];

        if (!is_null($likedBy)) {
            $retweetedStatus['liked_by'] = $likedBy;
        }

        return $retweetedStatus;
    }

    private function getTweetTemplate(array $tweet, ?array $tweetAsJSON = null): array
    {
        $template = [
            'avatar_url'     => 'N/A',
            'favorite_count' => 'N/A',
            'published_at'   => 'N/A',
            'retweet_count'  => 'N/A',
            'status_id'      => $tweet['status_id'],
            'text'           => 'N/A',
            'username'       => $tweet['screen_name'],
        ];

        if ($tweetAsJSON !== null) {
            $template['text'] = $this->extractText($tweet['text'], $tweetAsJSON);

            if ($template['status_id'] === null) {
                $template['status_id'] = $tweetAsJSON['id_str'];
            }
        }

        $template['url'] = 'https://twitter.com/'.$tweet['screen_name'].'/status/'.$tweet['status_id'];

        return $template;
    }

    private function guardAgainstMissingUpstreamTweetDocument(array $tweet): bool
    {
        return !array_key_exists('original_document', $tweet)
            && !array_key_exists('api_document', $tweet);
    }

    /**
     * @throws \JsonException
     */
    private function decodeUpstreamDocument(array $tweet): array
    {
        if (array_key_exists('api_document', $tweet) && empty($tweet['original_document'])) {
            $tweet['original_document'] = $tweet['api_document'];
            unset($tweet['api_document']);
        }

        return json_decode(
            $tweet['original_document'],
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    public function getDefaultProfilePictures(): array
    {
        return [
            'x1' => 'data:image/jpeg;base64,' . self::DEFAULT_PROFILE_PICTURE_1X,
            'x2' => 'data:image/jpeg;base64,' . self::DEFAULT_PROFILE_PICTURE_2X,
            'x3' => 'data:image/jpeg;base64,' . self::DEFAULT_PROFILE_PICTURE_3X
        ];
    }

    /**
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \JsonException
     */
    public function getExistingProfilePicturesOrFetchThem(array $tweet): array
    {
        $profilePictureHash = hash('sha256', $tweet['avatar_url']);

        $encodedProfilePicturePath = sprintf(
            '%s/%s.%s',
            $this->mediaDirectory,
            "profile_picture_{$profilePictureHash}",
            'b64'
        );

        if (file_exists($encodedProfilePicturePath)) {
            $encodedProfilePictures = \Safe\file_get_contents($encodedProfilePicturePath);

            $tweet['base64_encoded_avatar'] = json_decode($encodedProfilePictures, flags: JSON_THROW_ON_ERROR);

            return $tweet;
        }

        try {
            $memberProfilePicture = file_get_contents($tweet['avatar_url']);
            if ($memberProfilePicture === false) {
                throw new Exception('Could not fetch member profile picture');
            }

            $profilePictures = [
                'x1' => 'data:image/webp;base64,' . base64_encode(
                        Image::fromJpegProfilePictureToResizedWebp($memberProfilePicture)
                    ),
                'x2' => 'data:image/webp;base64,' . base64_encode(
                        Image::fromJpegProfilePictureToResizedWebp($memberProfilePicture, 2)
                    ),
                'x3' => 'data:image/webp;base64,' . base64_encode(
                        Image::fromJpegProfilePictureToResizedWebp($memberProfilePicture, 3)
                    ),
            ];
        } catch (Exception|ImageProcessingException) {
            $profilePictures = $this->getDefaultProfilePictures();
        }

        \Safe\file_put_contents($encodedProfilePicturePath, json_encode($profilePictures));

        $tweet['base64_encoded_avatar'] = $profilePictures;

        return $tweet;
    }
}
