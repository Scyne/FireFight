<?php

/*
 * This file is part of the Secret Santa project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliCode\SecretSanta\Discord;

use GuzzleHttp\Command\Exception\CommandClientException;
use JoliCode\SecretSanta\Exception\MessageSendFailedException;
use JoliCode\SecretSanta\Model\SecretSanta;

class MessageSender
{
    private $apiHelper;

    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    /**
     * @throws MessageSendFailedException
     */
    public function sendSecretMessage(SecretSanta $secretSanta, string $giver, string $receiver, bool $isSample): void
    {
        $text = '';

        if ($isSample) {
            $text .= "_Find below a sample of the message that will be sent to each members of your Secret Santa._\n----\n\n";
        }

        $text .= sprintf(
'Hi!

You have been chosen to be part of a FireFight!

**You are targeting:**
:boom: **<@%s>** :boom:
**That\'s a secret we only shared with you!**

Someone has also been chosen to target you. Watch your back, $receiver);

        if (!empty($secretSanta->getAdminMessage())) {
            $text .= "\n\nHere is a message from the game admin:\n\n```" . $secretSanta->getAdminMessage() . '```';
        }

        if ($secretSanta->getAdmin()) {
            $text .= sprintf("\n\n_Your Secret Santa admin, <@%s>._", $secretSanta->getAdmin()->getIdentifier());
        }

        $text .= "\n\n";
        $text .= '_If you see `@invalid-user` as the user you need to target, please read the message from desktop. There is a known bug in Discord Mobile applications._';

        try {
            $this->apiHelper->sendMessage($giver, $text);
        } catch (CommandClientException $e) {
            $precision = null;

            if (($response = $e->getResponse()) && 403 === $response->getStatusCode()) {
                $precision = 'The user does not allow to receive DM on this server. Please ask them to change their server settings.';
            }

            throw new MessageSendFailedException($secretSanta, $secretSanta->getUser($giver), $e, $precision);
        } catch (\Throwable $t) {
            throw new MessageSendFailedException($secretSanta, $secretSanta->getUser($giver), $t);
        }
    }

    /**
     * @throws MessageSendFailedException
     */
    public function sendAdminMessage(SecretSanta $secretSanta, string $code, string $spoilUrl): void
    {
        $text = sprintf(
'Dear game admin,

In case of trouble or if you need it for whatever reason, here is a way to retrieve the secret repartition:

- Copy the following content:
```%s```
- Paste the content on <%s> then submit

Remember, with great power comes great responsibility!

Happy fragging!',
            $code,
            $spoilUrl
        );

        try {
            $this->apiHelper->sendMessage($secretSanta->getAdmin()->getIdentifier(), $text);
        } catch (CommandClientException $e) {
            $precision = null;

            if (($response = $e->getResponse()) && 403 === $response->getStatusCode()) {
                $precision = 'You do not allow to receive DM on this server. Please change your server settings.';
            }

            throw new MessageSendFailedException($secretSanta, $secretSanta->getAdmin(), $e, $precision);
        } catch (\Throwable $t) {
            throw new MessageSendFailedException($secretSanta, $secretSanta->getAdmin(), $t);
        }
    }
}
