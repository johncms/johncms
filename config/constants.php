<?php

const DS = DIRECTORY_SEPARATOR;

define('ROOT_PATH', dirname(__DIR__) . DS);
const ASSETS_PATH = ROOT_PATH . 'assets' . DS;
const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const DATA_PATH = ROOT_PATH . 'data' . DS;
const UPLOAD_PATH = ROOT_PATH . 'upload' . DS;
const CACHE_PATH = DATA_PATH . 'cache' . DS;
const LOG_PATH = DATA_PATH . 'logs' . DS;
const THEMES_PATH = ROOT_PATH . 'themes' . DS;
const CMS_VERSION = '9.2.0';
