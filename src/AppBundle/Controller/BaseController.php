<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Instellingen;
use AppBundle\Entity\SendMail;
use AppBundle\Entity\Voorinschrijving;
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
    const OPENING_INSCHRIJVING = 'Opening inschrijving';
    const SLUITING_INSCHRIJVING_TURNSTERS = 'Sluiting inschrijving turnsters';
    const SLUITING_INSCHRIJVING_JURYLEDEN = 'Sluiting inschrijving juryleden';
    const SLUITING_UPLOADEN_VLOERMUZIEK = 'Sluiting uploaden vloermuziek';
    const MAX_AANTAL_TURNSTERS = 'Max aantal turnsters';

    protected $sponsors = array();
    protected $menuItems = array();

    protected function getOrganisatieInstellingen($fieldname = false)
    {
        $instellingen = array();
        if (!$fieldname) {
            $instellingKeys = array(
                self::OPENING_INSCHRIJVING,
                self::SLUITING_INSCHRIJVING_TURNSTERS,
                self::SLUITING_INSCHRIJVING_JURYLEDEN,
                self::SLUITING_UPLOADEN_VLOERMUZIEK,
                self::MAX_AANTAL_TURNSTERS,
            );
        } else {
            $instellingKeys = array($fieldname);
        }
        foreach ($instellingKeys as $key) {
            $result = $this->getDoctrine()
                ->getRepository('AppBundle:Instellingen')
                ->findBy(
                    array('instelling' => $key),
                    array('gewijzigd' => 'DESC')
                );
            if (count($result) > 0) {
                /** @var Instellingen $result */
                $result = $result[0];
            }
            if ($key == self::MAX_AANTAL_TURNSTERS) {
                $instellingen[$key] = ($result) ? $result->getAantal() : "Klik om te wijzigen";
            } else {
                $instellingen[$key] = ($result) ? $result->getDatum() : "Klik om te wijzigen";
                if ($result) {
                    $instellingen[$key] = $instellingen[$key]->format('d-m-Y H:i');
                }
            }
        }
        return $instellingen;
    }

    protected function usedVoorinschrijvingsToken($token)
    {
        /** @var Voorinschrijving $result */
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findOneBy(
                array('token' => $token)
            );
        $result->setUsedAt(new \DateTime('now'));
        $this->addToDB($result);
    }

    protected function checkVoorinschrijvingsToken($token)
    {
        if ($token === null) {
            return false;
        } else {
            /** @var Voorinschrijving $result */
            $result = $this->getDoctrine()
                ->getRepository('AppBundle:Voorinschrijving')
                ->findOneBy(
                    array('token' => $token)
                );
            if ($result) {
                if ($result->getUsedAt() === null) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function inschrijvingToegestaan($token = null)
    {
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_TURNSTERS);
        if ((time() > strtotime($instellingGeopend[self::OPENING_INSCHRIJVING]) &&
                time() < strtotime($instellingGesloten[self::SLUITING_INSCHRIJVING_TURNSTERS])) ||
            $this->checkVoorinschrijvingsToken($token)
        ) {
            return true;
        }
        return false;
    }

    protected function wijzigTurnsterToegestaan()
    {
        /** @var \DateTime[] $instellingGeopend */
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        /** @var \DateTime[] $instellingGesloten */
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_TURNSTERS);
        if ((time() > $instellingGeopend[self::OPENING_INSCHRIJVING]->getTimestamp() &&
            time() < $instellingGesloten[self::SLUITING_INSCHRIJVING_TURNSTERS]->getTimestamp())
        ) {
            return true;
        }
        return false;
    }

    protected function wijzigJuryToegestaan()
    {
        /** @var \DateTime[] $instellingGeopend */
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        /** @var \DateTime[] $instellingGesloten */
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_JURYLEDEN);
        if ((time() > $instellingGeopend[self::OPENING_INSCHRIJVING]->getTimestamp() &&
            time() < $instellingGesloten[self::SLUITING_INSCHRIJVING_JURYLEDEN]->getTimestamp())
        ) {
            return true;
        } else {
            return false;
        }
    }

    protected function uploadenVloermuziekToegestaan()
    {
        //todo: deze functie schrijven
    }

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
        if (in_array($page, ['Inschrijvingsinformatie'])) return true;
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
     * Creates a token voor voorinschrijvingen
     * @return void
     */
    protected function createVoorinschrijvingToken($email)
    {
        $token = sha1(mt_rand());
        $tokenObject = new Voorinschrijving();
        $tokenObject->setToken($token);
        $tokenObject->setCreatedAt(new \DateTime('now'));
        $tokenObject->setTokenSentTo($email);

        $subject = 'Voorinschrijving HBC';
        $to = $email;
        $view = 'mails/voorinschrijving.txt.twig';
        $mailParameters = [
            'token' =>$token,
        ];
        $this->sendEmail($subject, $to, $view, $mailParameters);
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
