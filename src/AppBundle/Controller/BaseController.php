<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;


class BaseController extends Controller
{
    protected $sponsors;
    protected $menuItems;

    protected function __construct()
    {
        $this->session = new Session();
    }

    private function getSponsors()
    {

    }

    private function getMenuItems()
    {

    }

    protected function maand($maandNummer)
    {
        switch($maandNummer)
        {
            case '01': return 'Januari'; break;
            case '02': return 'Februari'; break;
            case '03': return 'Maart'; break;
            case '04': return 'April'; break;
            case '05': return 'Mei'; break;
            case '06': return 'Juni'; break;
            case '07': return 'Juli'; break;
            case '08': return 'Augustus'; break;
            case '09': return 'September'; break;
            case '10': return 'Oktober'; break;
            case '11': return 'November'; break;
            case '12': return 'December'; break;
        }
    }

    protected function generatePassword($length = 8)
    {
        $password = "";
        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
        $maxlength = strlen($possible);
        if ($length > $maxlength)
        {
            $length = $maxlength;
        }
        $i = 0;
        while ($i < $length)
        {
            $char = substr($possible, mt_rand(0, $maxlength-1), 1);
            if (!strstr($password, $char))
            {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }

    protected function setBasicPageData()
    {
        $this->menuItems = $this->getMenuItems();
        $this->sponsors = $this->getSponsors();
    }
}