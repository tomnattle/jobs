<?php
namespace executer;
use slaver\type;

class executerFactory
{
	static $executers = [];

    public static function load($name)
    {
    	if(isset($executers[$name]))
    	{
    		return $executers[$name];
    	}

        switch ($name) {
        	case type::MAIL_SPLIT:
        		$executers[$name] = splitFactory::create();
        		break;
        	case type::MAIL_SEND:
        		$executers[$name] = sendFactory::create();
        		break;
    		case type::CONTACT_IMPORT:
    			$executers[$name] = importFactory::create();
    			break;
        	case type::CONTACT_EXPORT:
        		$executers[$name] = exportFactory::create();
        		break;
        	case type::DELETE_COLUMN:
        		$executers[$name] = deleteColumnFactory::create();
        		break;
        	case type::REPORT_EXPORT:
        		$executers[$name] = exportReportFactory::create();
        		break;
        	default:
        		throw new Exception("unknow type of executer", 1);
        		
        }

        return $executers[$name];
    }
}
