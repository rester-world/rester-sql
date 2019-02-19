<?php
if(!defined('__RESTER__')) exit;

use rester\sql\rester;
//return rester::call_module('access_level_example','access_private');
return rester::call_proc('access_private');

