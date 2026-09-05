<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

interface WebSocketActions
{
    //-- CHANNEL TYPES (connection `?type=` in the query string). A channel is one feature
    //-- (shell, compose, ...). The `?type` decides how a connection authorizes, what actions
    //-- it accepts, and what lifecycle it gets.
    public const TYPE_SHELL   = 'shell';
    public const TYPE_COMPOSE = 'compose';

    //-- ACTION ROUTING KEYS (message JSON `action`; see WebSocketMessages::onMessage)
    public const ACTION_RESIZE  = 'resize';
    public const ACTION_COMPOSE = 'compose';
}
