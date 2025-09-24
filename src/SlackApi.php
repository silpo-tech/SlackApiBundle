<?php

declare(strict_types=1);

namespace SlackApiBundle;

use JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200;
use JoliCode\Slack\Api\Model\ChatPostMessagePostResponsedefault;
use JoliCode\Slack\Api\Model\ConversationsListGetResponse200;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Client;
use JoliCode\Slack\Exception\SlackErrorResponse;
use SlackApiBundle\Exception\SlackChanelNotFoundException;
use SlackApiBundle\Exception\SlackErrorBadResponseException;
use SlackApiBundle\Exception\SlackResponseErrorException;

final class SlackApi
{
    /** @var array<string, ObjsConversation> */
    private array $cache = [];

    public function __construct(public readonly Client $client, private readonly SlackApiOptions $options)
    {
    }

    /**
     * @throws SlackChanelNotFoundException
     */
    public function getChannelByName(string $channelName): ObjsConversation
    {
        $conversation = $this->getChannelConversationByName($channelName);

        if (null === $conversation) {
            throw new SlackChanelNotFoundException($channelName);
        }

        return $conversation;
    }

    public function sendMessageToChannelById(
        string $channelId,
        string $text,
        array $options = [],
    ): ChatPostMessagePostResponse200|ChatPostMessagePostResponsedefault {
        try {
            $response = $this->client->chatPostMessage([
                'channel' => $channelId,
                'text' => $text,
                ...$options,
            ]);
        } catch (SlackErrorResponse $ex) {
            throw new SlackResponseErrorException($ex);
        }

        if (
            !($response instanceof ChatPostMessagePostResponse200)
            && !($response instanceof ChatPostMessagePostResponsedefault)
        ) {
            throw new SlackErrorBadResponseException('Error while sending message to channel chat.');
        }

        return $response;
    }

    public function sendMessageToChannelByName(
        string $channelName,
        string $text,
        array $options = [],
    ): ChatPostMessagePostResponse200|ChatPostMessagePostResponsedefault {
        $channelId = $this->getChannelByName($channelName)->getId();

        return $this->sendMessageToChannelById($channelId, $text, $options);
    }

    private function getConversationScopes(): string
    {
        $scopes = [];

        if ($this->options->allowPrivateChannels) {
            $scopes[] = 'private_channel';
        }

        if ($this->options->allowPublicChannels) {
            $scopes[] = 'public_channels';
        }

        return implode(',', $scopes);
    }

    private function recursiveGetChannelByName(
        string $scopes,
        string $channelName,
        string|null $cursor = null,
    ): ObjsConversation|null {
        $options = ['types' => $scopes];
        if (null !== $cursor) {
            $options['cursor'] = $cursor;
        }

        /** @var ConversationsListGetResponse200|SlackErrorResponse $response */
        $response = $this->client->conversationsList($options);

        if ($response instanceof SlackErrorResponse) {
            throw new SlackResponseErrorException($response);
        }

        $closure = static fn (ObjsConversation $channel) => $channel->getIsChannel()
            && $channelName === $channel->getName();
        /** @var ObjsConversation[] $filteredChannels */
        $filteredChannels = array_filter(
            $response->getChannels(),
            $closure,
        );

        if ([] !== $filteredChannels) {
            return reset($filteredChannels);
        }

        if (null === $response->getResponseMetadata() || null === $response->getResponseMetadata()->getNextCursor()) {
            return null;
        }

        return $this->recursiveGetChannelByName(
            $scopes,
            $channelName,
            $response->getResponseMetadata()->getNextCursor(),
        );
    }

    public function getChannelConversationByName(string $channelName): ObjsConversation|null
    {
        if (isset($this->cache[$channelName])) {
            return $this->cache[$channelName];
        }

        return $this->cache[$channelName] = $this->recursiveGetChannelByName(
            $this->getConversationScopes(),
            $channelName,
        );
    }
}
