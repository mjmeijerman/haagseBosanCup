<?php

namespace AppBundle\Controller;

use AppBundle\Entity\SendMail;
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

    private function setSponsors()
    {
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Sponsor')
            ->findAll();
        foreach ($results as $result) {
            $this->sponsors[] = $result->getAll();
        }
        shuffle($this->sponsors);
    }

    private function setMenuItems($type)
    {
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:' . $type . 'menuItem')
            ->findAll();
        foreach ($results as $result) {
            $this->menuItems[] = $result->getAll();
        }
    }

    protected function checkIfPageExists($page)
    {
        $pageExists = false;
        foreach ($this->menuItems as $menuItem) {
            if ($menuItem['naam'] == $page) {
                $pageExists = true;
                break;
            }
            if ($menuItem['submenuItems']) {
                foreach ($menuItem['submenuItems'] as $submenuItem) {
                    if ($submenuItem['naam'] == $page) {
                        $pageExists = true;
                        break;
                    }
                }
            }
            if ($pageExists) {
                break;
            }
        }
        return $pageExists;
    }

    protected function maand($maandNummer)
    {
        switch ($maandNummer) {
            case '01':
                return 'Januari';
                break;
            case '02':
                return 'Februari';
                break;
            case '03':
                return 'Maart';
                break;
            case '04':
                return 'April';
                break;
            case '05':
                return 'Mei';
                break;
            case '06':
                return 'Juni';
                break;
            case '07':
                return 'Juli';
                break;
            case '08':
                return 'Augustus';
                break;
            case '09':
                return 'September';
                break;
            case '10':
                return 'Oktober';
                break;
            case '11':
                return 'November';
                break;
            case '12':
                return 'December';
                break;
        }
    }

    protected function generatePassword($length = 8)
    {
        $password = "";
        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
        $maxlength = strlen($possible);
        if ($length > $maxlength) {
            $length = $maxlength;
        }
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }

    protected function setBasicPageData($type = 'Hoofd')
    {
        $this->setMenuItems($type);
        $this->setSponsors();
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

    protected function sendEmail($subject, $to, $view, array $parameters = array(), $from = 'info@haagsebosancup.nl')
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->renderView(
                    $view,
                    array('parameters' => $parameters)
                ),
                'text/plain'
            );
        $this->get('mailer')->send($message);

        $sendMail = new SendMail();
        $sendMail->setDatum(new \DateTime())
            ->setVan($from)
            ->setAan($to)
            ->setOnderwerp($subject)
            ->setBericht($message->getBody());
        $this->addToDB($sendMail);
    }

    protected function addToDB($object, $detach = null)
    {
        $em = $this->getDoctrine()->getManager();
        if ($detach) {
            $em->detach($detach);
        }
        $em->persist($object);
        $em->flush();
    }

    protected function removeFromDB($object)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($object);
        $em->flush();
    }
}
