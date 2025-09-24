<?php

declare(strict_types=1);

namespace SlackApiBundle\Tests\TestCase\Unit;

use Faker\Factory;
use JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200;
use JoliCode\Slack\Api\Model\ConversationsListGetResponse200;
use JoliCode\Slack\Api\Model\ConversationsListGetResponse200ResponseMetadata;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Client;
use JoliCode\Slack\Exception\SlackErrorResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use SlackApiBundle\Exception\SlackChanelNotFoundException;
use SlackApiBundle\Exception\SlackErrorBadResponseException;
use SlackApiBundle\Exception\SlackResponseErrorException;
use SlackApiBundle\SlackApi;
use SlackApiBundle\SlackApiOptions;

class SlackApiTest extends TestCase
{
    private const string CHANNEL_NAME = 'channel-name';

    /**
     * @throws MockException
     */
    public function testSuccess(): void
    {
        $faker = Factory::create();

        $mockClient = $this->createMock(Client::class);

        $response = new ChatPostMessagePostResponse200();
        $mockClient->expects($this->once())->method('chatPostMessage')->willReturn($response);

        $metadata = new ConversationsListGetResponse200ResponseMetadata();
        $metadata->setNextCursor($faker->word());

        $channel1 = new ObjsConversation();
        $channel1->setId($faker->uuid());

        $response1 = new ConversationsListGetResponse200();
        $response1->setChannels([$channel1]);
        $response1->setResponseMetadata($metadata);

        $channel2 = new ObjsConversation();
        $channel2->setIsChannel(true);
        $channel2->setName(self::CHANNEL_NAME);
        $channel2->setId($faker->uuid());

        $response2 = new ConversationsListGetResponse200();
        $response2->setChannels([$channel2]);

        $mockClient
            ->expects($this->atLeast(2))
            ->method('conversationsList')
            ->willReturnOnConsecutiveCalls($response1, $response2)
        ;

        $client = new SlackApi($mockClient, SlackApiOptions::create(['allow_private_channels' => true]));
        $response = $client->sendMessageToChannelByName(self::CHANNEL_NAME, $faker->sentence());

        $this->assertInstanceOf(ChatPostMessagePostResponse200::class, $response);
    }

    /**
     * @throws MockException
     */
    #[DataProvider('exceptionsDataProvider')]
    public function testExceptions(array $data, array $expected): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())->method('conversationsList')->willReturn($data['listResponse']());

        if (array_key_exists('exception', $data['chatResponse'])) {
            $mockClient
                ->expects($this->once())
                ->method('chatPostMessage')
                ->willThrowException($data['chatResponse']['exception'])
            ;
        }

        if (array_key_exists('return', $data['chatResponse'])) {
            $mockClient
                ->expects($this->once())
                ->method('chatPostMessage')
                ->willReturn($data['chatResponse']['return'])
            ;
        }

        $this->expectException($expected['exception']);

        $client = new SlackApi($mockClient, SlackApiOptions::create([]));
        $client->sendMessageToChannelByName(self::CHANNEL_NAME, 'some-text');
    }

    public static function exceptionsDataProvider(): iterable
    {
        yield 'slack channel not found' => [
            'data' => [
                'listResponse' => static function () {
                    $channel = new ObjsConversation();
                    $response = new ConversationsListGetResponse200();
                    $response->setChannels([$channel]);

                    return $response;
                },
                'chatResponse' => [],
            ],
            'expected' => [
                'exception' => SlackChanelNotFoundException::class,
            ],
        ];

        yield 'slack response exception' => [
            'data' => [
                'listResponse' => static fn () => new SlackErrorResponse('500', null),
                'chatResponse' => [],
            ],
            'expected' => [
                'exception' => SlackResponseErrorException::class,
            ],
        ];

        yield 'slack response error' => [
            'data' => [
                'listResponse' => static function () {
                    $channel = new ObjsConversation();
                    $channel->setIsChannel(true);
                    $channel->setName(self::CHANNEL_NAME);
                    $channel->setId('id');

                    $response = new ConversationsListGetResponse200();
                    $response->setChannels([$channel]);

                    return $response;
                },
                'chatResponse' => [
                    'exception' => new SlackErrorResponse('error', []),
                ],
            ],
            'expected' => [
                'exception' => SlackResponseErrorException::class,
            ],
        ];

        yield 'slack error bad response' => [
            'data' => [
                'listResponse' => static function () {
                    $channel = new ObjsConversation();
                    $channel->setIsChannel(true);
                    $channel->setName(self::CHANNEL_NAME);
                    $channel->setId('id');

                    $response = new ConversationsListGetResponse200();
                    $response->setChannels([$channel]);

                    return $response;
                },
                'chatResponse' => [
                    'return' => null,
                ],
            ],
            'expected' => [
                'exception' => SlackErrorBadResponseException::class,
            ],
        ];
    }
}
