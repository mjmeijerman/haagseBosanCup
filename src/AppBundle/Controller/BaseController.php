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
    protected $sponsors = array();
    protected $menuItems = array();

    private function getSponsors()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT sponsor
            FROM AppBundle:Sponsor sponsor');
        $content = $query->getResult();
        for($i=0;$i<count($content);$i++)
        {
            $this->sponsors[$i] = $content[$i]->getAll();
        }
        shuffle($this->sponsors);
    }

    private function getMenuItems()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT hoofdmenuitem
            FROM AppBundle:HoofdmenuItem hoofdmenuitem
            ORDER BY hoofdmenuitem.positie');
        $results = $query->getResult();
        for($i = 0; $i < count($results); $i++) {
            $this->menuItems['hoofdmenuItems'][$i]['naam'] = $results[$i]->getNaam();
            $this->menuItems['hoofdmenuItems'][$i]['id'] = $results[$i]->getId();
            $submenuItems = $results[$i]->getSubmenuItems();
            for ($j = 0; $j < count($submenuItems); $j++) {
                $this->menuItems['hoofdmenuItems'][$i]['submenuItems'][$j]['naam'] = $submenuItems[$j]->getNaam();
                $this->menuItems['hoofdmenuItems'][$i]['submenuItems'][$j]['id'] = $submenuItems[$j]->getId();
            }
        }
    }

    protected function checkIfPageExists($page)
    {
        $pageExists = false;
        foreach ($this->menuItems['hoofdmenuItems'] as $item) {
            if ($pageExists) break;
            if ($item['naam'] == $page) {
                $pageExists = true;
                break;
            }
            if (isset($item['submenuItems'])) {
                foreach ($item['submenuItems'] as $subItem) {
                    if ($subItem['naam'] == $page) {
                        $pageExists = true;
                        break;
                    }
                }
            }
        }
        return $pageExists;
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
        $this->getMenuItems();
        $this->getSponsors();
    }

    /**
     * Creates a token usable in a form
     * @return string
     */
    protected function getToken()
    {
        $token = sha1(mt_rand());
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens'] = array($token => 1);
        } else {
            $_SESSION['tokens'][$token] = 1;
        }
        return $token;
    }

    /**
     * Check if a token is valid. Removes it from the valid tokens list
     * @param string $token The token
     * @return bool
     */
    protected function isTokenValid($token)
    {
        if (!empty($_SESSION['tokens'][$token])) {
            unset($_SESSION['tokens'][$token]);
            return true;
        }
        return false;
    }
}