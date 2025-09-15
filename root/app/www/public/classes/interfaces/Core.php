<?php

/*
----------------------------------
 ------  Created: 082725   ------
 ------  Austin Best	   ------
----------------------------------
*/

interface AccessMode
{
    public const RWX = 0; //-- FULL ACCESS
    public const RW = 1; //-- REMOVE ACCESS TO DELETE, ALLOW SOME ADD AND MOST UPDATE ACTIONS
    public const R = 2; //-- REMOVE ACCESS TO ADD/DELETE, ALLOW VERY MINIMAL UPDATE ACTIONS

}
