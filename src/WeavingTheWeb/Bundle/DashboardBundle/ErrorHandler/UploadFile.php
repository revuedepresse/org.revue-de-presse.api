<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ErrorHandler;

use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;

use Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UploadFile implements ErrorHandlerInterface
{
    public function addException(AbstractResponse $response, \Exception $exception)
    {
        $message = $exception->getMessage();
        $response['error'] = json_decode($message, $asAssociativeArray = true);
    }
}
