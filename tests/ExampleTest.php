<?php

use AwaisJameel\Base64FileHandler\Base64FileHandler;

it('can create instance of Base64FileHandler', function () {
    $handler = new Base64FileHandler();
    expect($handler)->toBeInstanceOf(Base64FileHandler::class);
});