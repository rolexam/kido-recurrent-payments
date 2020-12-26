<?php


namespace Kido\Services;


class Settings
{

    protected static $ARCA_Username;
    protected static $ARCA_Password;
    protected static $DB_Host;
    protected static $DB_DBName;
    protected static $DB_Username;
    protected static $DB_Password;

    public static function enrich(array $data = [])
    {

    }

    /**
     * @return string
     */
    public static function getARCAUsername(): string
    {
        return self::$ARCA_Username;
    }

    /**
     * @return string
     */
    public static function getARCAPassword(): string
    {
        return self::$ARCA_Password;
    }

    /**
     * @return mixed
     */
    public static function getDBHost()
    {
        return self::$DB_Host;
    }

    /**
     * @return mixed
     */
    public static function getDBDBName()
    {
        return self::$DB_DBName;
    }

    /**
     * @return mixed
     */
    public static function getDBUsername()
    {
        return self::$DB_Username;
    }

    /**
     * @return mixed
     */
    public static function getDBPassword()
    {
        return self::$DB_Password;
    }

}