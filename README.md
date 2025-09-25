# Slack API Bundle for Symfony Framework #

[![CI](https://github.com/silpo-tech/SlackApiBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/silpo-tech/SlackApiBundle/actions)
[![codecov](https://codecov.io/gh/silpo-tech/SlackApiBundle/graph/badge.svg)](https://codecov.io/gh/silpo-tech/SlackApiBundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Installation

The suggested installation method's via [composer](https://getcomposer.org/):

```sh
composer require silpo-tech/slack-api-bundle
```

## Use

config/packages/slackapi.yml
```yaml
slack_api:
    token: '%env(SLACK_API_TOKEN)%'
    options:
        # This option will add private_channel scope to conversationList request
        allow_private_channels: true
        # This option will add public_channels scope to conversationList request
        allow_public_channels: true
```

## Tests ##

```shell
composer test:run
```