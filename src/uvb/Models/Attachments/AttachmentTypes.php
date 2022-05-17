<?php

namespace uvb\Models\Attachments;

use Data\Enum;

class AttachmentTypes extends Enum
{
    const PHOTO = "photo";
    const VIDEO = "video";
    const AUDIO = "audio";
    const DOCUMENT = "doc";
    const WALL = "wall";
    const MARKET = "market";
    const POLL = "poll";
    const STICKER = "sticker";
    const AUDIO_MESSAGE = "audio_message";
}