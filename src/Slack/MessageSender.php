<?php

/*
 * This file is part of the Secret Santa project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliCode\SecretSanta\Slack;

use JoliCode\SecretSanta\Exception\MessageSendFailedException;
use JoliCode\SecretSanta\Model\SecretSanta;

class MessageSender
{
    private $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @throws MessageSendFailedException
     */
    public function sendSecretMessage(SecretSanta $secretSanta, string $giver, string $receiver, string $token, bool $isSample): void
    {
        $fallbackText = '';
        $blocks = [];

        if ($isSample) {
            $blocks[] = [
                'type' => 'context',
                'elements' => [
                    ['type' => 'mrkdwn', 'text' => '_Find below a sample of the message that will be sent to each members of your game._'],
                ],
            ];
        }

        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => sprintf("Hi!\nYou have been chosen to be part of a FireFight Game!\n\n"),
            ],
        ];

        $receiverUser = $secretSanta->getUser($receiver);
        $receiverBlock = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => sprintf("*Your target is:*\n\n:dart: *<@%s>* :dart:\n\n", $receiver),
            ],
        ];

        if ($receiverUser->getExtra() && array_key_exists('image', $receiverUser->getExtra())) {
            $receiverBlock['accessory'] = [
                'type' => 'image',
                'image_url' => $receiverUser->getExtra()['image'],
                'alt_text' => $receiverUser->getName(),
            ];
        }

        $blocks[] = $receiverBlock;

        $fallbackText .= sprintf('You have been chosen to be part of a FireFight Game!
*Your Target is:* :dart: *<@%s>* :dart:', $receiver);

        if (!empty($secretSanta->getAdminMessage())) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf('*Here is a message from the game admin _(<@%s>)_:*', $secretSanta->getAdmin()->getIdentifier()),
                ],
            ];

            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $secretSanta->getAdminMessage(),
                ],
            ];

            $fallbackText .= sprintf("\n\nHere is a message from the game admin _(<@%s>)_:\n\n```%s```", $secretSanta->getAdmin()->getIdentifier(), $secretSanta->getAdminMessage());
        } else {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf('_If you have any question please ask your Game Admin: <@%s>_', $secretSanta->getAdmin()->getIdentifier()),
                ],
            ];
        }

        $blocks[] = [
            'type' => 'divider',
        ];

        $blocks[] = [
            'type' => 'context',
            'elements' => [
                ['type' => 'plain_text', 'text' => 'That\'s a secret only shared with you! Someone has also been chosen to target you. Watch your back!'],
                ['type' => 'mrkdwn', 'text' => 'Powered by The FireFight Targeting System'],
            ],
        ];

        try {
            $response = $this->clientFactory->getClientForToken($token)->chatPostMessage([
                'channel' => sprintf('@%s', $giver),
                'username' => $isSample ? 'FireFight Preview' : 'FireFight Targeting System',
                'icon_url' => 'https://cdn.imgbin.com/23/8/15/imgbin-terri-brosius-system-shock-2-shodan-video-game-others-AqSXxPX2rjEkGZVwHftScKZR8.jpg',
                'text' => $fallbackText,
                'blocks' => \json_encode($blocks),
            ]);

            if (!$response->getOk()) {
                throw new MessageSendFailedException($secretSanta, $secretSanta->getUser($giver));
            }
        } catch (\Throwable $t) {
            throw new MessageSendFailedException($secretSanta, $secretSanta->getUser($giver), $t);
        }
    }

    /**
     * @throws MessageSendFailedException
     */
    public function sendAdminMessage(SecretSanta $secretSanta, string $code, string $spoilUrl, string $token): void
    {
        $text = sprintf(
            'Dear Game admin,

In case of trouble or if you need it for whatever reason, here is a way to retrieve the secret repartition:

- Copy the following content:
```%s```
- Paste the content on <%s|this page> then submit

Remember, with great power comes great responsibility!

Happy Fragging!',
            $code,
            $spoilUrl
        );

        try {
            $response = $this->clientFactory->getClientForToken($token)->chatPostMessage([
                'channel' => $secretSanta->getAdmin()->getIdentifier(),
                'username' => 'FireFight Spoiler',
                'icon_url' => 'https://img.fireden.net/v/image/1492/02/1492027545775.png',
                'text' => $text,
            ]);

            if (!$response->getOk()) {
                throw new MessageSendFailedException($secretSanta, $secretSanta->getAdmin());
            }
        } catch (\Throwable $t) {
            throw new MessageSendFailedException($secretSanta, $secretSanta->getAdmin(), $t);
        }
    }
}
