<?php

declare(strict_types=1);

namespace SlackApiBundle;

final class SlackApiOptions
{
    public bool $allowPrivateChannels;
    public bool $allowPublicChannels;

    public static function create(array $options): self
    {
        $self = new self();
        $self->allowPrivateChannels = $options['allow_private_channels'] ?? false;
        $self->allowPublicChannels = $options['allow_public_channels'] ?? true;

        return $self;
    }
}
