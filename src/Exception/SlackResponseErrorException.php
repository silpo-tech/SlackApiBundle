<?php

declare(strict_types=1);

namespace SlackApiBundle\Exception;

use JoliCode\Slack\Exception\SlackErrorResponse;

final class SlackResponseErrorException extends \RuntimeException
{
    private SlackErrorResponse $response;

    public function __construct(SlackErrorResponse $response)
    {
        parent::__construct('Error occurred while getting response', 0, $response->getPrevious());
        $this->response = $response;
    }

    public function getResponse(): SlackErrorResponse
    {
        return $this->response;
    }
}
