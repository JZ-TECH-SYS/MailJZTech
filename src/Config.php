<?php

namespace src;

require_once __DIR__ . '/Env.php';

class Config
{
    const BASE_DIR = BASE_DIR;
    const TOKEN_JV = TOKEN_JV;

    const DB_DRIVER = DB_DRIVER;
    const DB_HOST = DB_HOST;
    const DB_PORT = DB_PORT;
    const DB_DATABASE = DB_DATABASE;
    const DB_USER = DB_USER;
    const DB_PASS = DB_PASS;

    const FRONT_URL = FRONT_URL;

    const EMAIL_API = EMAIL_API;
    const SENHA_EMAIL_API = SENHA_EMAIL_API;
    const SMTP_PORT = SMTP_PORT;
    const SMTP_HOST = SMTP_HOST;

    const ERROR_CONTROLLER = 'ErrorController';
    const DEFAULT_ACTION = 'index';
}
