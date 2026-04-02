<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Filter Phrase
    |--------------------------------------------------------------------------
    |
    | Any PDF page whose text content contains this phrase (case-insensitive)
    | will be automatically excluded from the merged output. Set to an empty
    | string to disable auto-filtering.
    |
    */

    'exclude_phrase' => 'Your GLS Track-ID',

    /*
    |--------------------------------------------------------------------------
    | Email Defaults
    |--------------------------------------------------------------------------
    |
    | Default values pre-filled in the "Send via email" dialog. Any of these
    | can also be overridden fluently on the plugin via
    | ->emailRecipient(), ->emailSubject(), ->emailMessage().
    |
    */

    'email_recipient' => '',

    'email_subject'   => 'Merged PDF',

    'email_message'   => 'Please find the merged PDF attached.',

];
