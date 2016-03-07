<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Instellingen;
use AppBundle\Entity\SendMail;
use AppBundle\Entity\Turnster;
use AppBundle\Entity\User;
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
    const OPENING_UPLOADEN_VLOERMUZIEK = 'Opening uploaden vloermuziek';
    const SLUITING_INSCHRIJVING_TURNSTERS = 'Sluiting inschrijving turnsters';
    const SLUITING_INSCHRIJVING_JURYLEDEN = 'Sluiting inschrijving juryleden';
    const SLUITING_UPLOADEN_VLOERMUZIEK = 'Sluiting uploaden vloermuziek';
    const FACTUUR_BEKIJKEN_TOEGESTAAN = 'Factuur publiceren';
    const MAX_AANTAL_TURNSTERS = 'Max aantal turnsters';

    protected $sponsors = array();
    protected $menuItems = array();

    protected function getOrganisatieInstellingen($fieldname = false)
    {
        $instellingen = array();
        if (!$fieldname) {
            $instellingKeys = array(
                self::OPENING_INSCHRIJVING,
                self::OPENING_UPLOADEN_VLOERMUZIEK,
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

    protected function isKeuzeOefenstof($geboorteJaar)
    {
        $leeftijd = (date('Y', time())-$geboorteJaar);
        if ($leeftijd >= 13) {
            return true;
        }
        return false;
    }

    protected function checkVoorinschrijvingsToken($token, Session $session = null)
    {
        if ($token === null) {
            return false;
        } elseif ($session == null) {
            return false;
        } elseif ($token == $session->get('token')) {
            return true;
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

    protected function updateGereserveerdePlekken()
    {
        /** @var Turnster[] $gereserveerdePlekken */
        $gereserveerdePlekken = $this->getDoctrine()->getRepository('AppBundle:Turnster')
            ->getGereserveerdePlekken();
        foreach ($gereserveerdePlekken as $gereserveerdePlek) {
            if ($gereserveerdePlek->getExpirationDate() < new \DateTime('now')) {
                $this->removeFromDB($gereserveerdePlek);
            }
        }
        $sluitingInschrijving = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_TURNSTERS);
        if (strtotime($sluitingInschrijving[self::SLUITING_INSCHRIJVING_TURNSTERS]) > time()) {
            /** @var Turnster[] $wachtlijstPlekken */
            $wachtlijstPlekken = $this->getDoctrine()->getRepository('AppBundle:Turnster')
                ->getWachtlijstPlekken($this->getVrijePlekken());
            foreach ($wachtlijstPlekken as $wachtlijstPlek) {
                $wachtlijstPlek->setWachtlijst(false);
                $this->addToDB($wachtlijstPlek);
            }
        }
        /** @var User[] $contactpersonen */
//        $contactpersonen = $this->getDoctrine()->getRepository('AppBundle:User')
//            ->loadUsersByRole('ROLE_CONTACT');
//        foreach ($contactpersonen as $contactpersoon) {
//            if (count($contactpersoon->getTurnster()) == 0) {
//                $this->removeFromDB($contactpersoon);
//            }
//        }
    }

    protected function getCategorie($geboorteJaar)
    {
        $leeftijd = (date('Y', time())-$geboorteJaar);
        if ($leeftijd < 8) {
            return '';
        } elseif ($leeftijd == 8) {
            return 'Voorinstap';
        } elseif ($leeftijd == 9) {
            return 'Instap';
        } elseif ($leeftijd == 10) {
            return 'Pupil 1';
        } elseif ($leeftijd == 11) {
            return 'Pupil 2';
        } elseif ($leeftijd == 12) {
            return 'Jeugd 1';
        } elseif ($leeftijd == 13) {
            return 'Jeugd 2';
        } elseif ($leeftijd == 14 || $leeftijd == 15) {
            return 'Junior';
        } else {
            return 'Senior';
        }
    }

    protected function getAvailableNiveaus($geboorteJaar)
    {
        $leeftijd = (date('Y', time())-$geboorteJaar);
        if ($leeftijd < 8) {
            return [];
        } elseif ($leeftijd == 8 || $leeftijd == 9) {
            return ['N2', 'D1', 'D2'];
        } elseif ($leeftijd == 10 || $leeftijd == 11) {
            return ['N3', 'D1', 'D2'];
        } elseif ($leeftijd == 12) {
            return ['N4', 'D1', 'D2'];
        } else {
            return ['Div. 3', 'Div. 4', 'Div. 5'];
        }
    }

    protected function getGeboorteJaren()
    {
        $geboorteJaren = [];
        for ($i = (date('Y', time())-8); $i >= 1950 ; $i--) {
            $geboorteJaren[] = $i;
        }
        return $geboorteJaren;
    }

    protected function getVrijePlekken()
    {
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Turnster')
            ->getBezettePlekken();
        $maxPlekken = $this->getOrganisatieInstellingen(self::MAX_AANTAL_TURNSTERS);
        if ($maxPlekken[self::MAX_AANTAL_TURNSTERS] - $result < 0) {
            return 0;
        }
        return ($maxPlekken[self::MAX_AANTAL_TURNSTERS] - $result);
    }

    protected function getTijdVol()
    {
        $datumGeopend = 0;
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Instellingen')
            ->findBy(
                array('instelling' => self::OPENING_INSCHRIJVING),
                array('gewijzigd' => 'DESC')
            );
        if (count($result) > 0) {
            $datumGeopend = $result[0];
        }
        /** @var Instellingen $result */
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Instellingen')
            ->getTijdVol($datumGeopend);
        if ($result) {
            return $result->getDatum();
        } else {
            $result = $this->getDoctrine()
                ->getRepository('AppBundle:Turnster')
                ->getTijdVol();
            $instelling = new Instellingen();
            $instelling->setInstelling('tijdVol');
            $instelling->setGewijzigd(new \DateTime('now'));
            $instelling->setDatum($result[0]['creationDate']);
            $this->addToDB($instelling);
            $result = $this->getDoctrine()
                ->getRepository('AppBundle:Instellingen')
                ->getTijdVol($datumGeopend);
            return $result->getDatum();
        }
    }

    protected function inschrijvingToegestaan($token = null, Session $session = null)
    {
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_TURNSTERS);
        if ((time() > strtotime($instellingGeopend[self::OPENING_INSCHRIJVING]) &&
                time() < strtotime($instellingGesloten[self::SLUITING_INSCHRIJVING_TURNSTERS])) ||
            $this->checkVoorinschrijvingsToken($token, $session)
        ) {
            return true;
        }
        return false;
    }

    protected function wijzigTurnsterToegestaan()
    {
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_TURNSTERS);
        if ((time() > strtotime($instellingGeopend[self::OPENING_INSCHRIJVING]) &&
            time() < strtotime($instellingGesloten[self::SLUITING_INSCHRIJVING_TURNSTERS]))
        ) {
            return true;
        }
        return false;
    }

    protected function verwijderenTurnsterToegestaan()
    {
        /** @var \DateTime[] $instellingGeopend */
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        if ((time() > strtotime($instellingGeopend[self::OPENING_INSCHRIJVING]))
        ) {
            return true;
        }
        return false;
    }

    protected function wijzigJuryToegestaan()
    {
        $instellingGeopend = $this->getOrganisatieInstellingen(self::OPENING_INSCHRIJVING);
        $instellingGesloten = $this->getOrganisatieInstellingen(self::SLUITING_INSCHRIJVING_JURYLEDEN);
        if ((time() > strtotime($instellingGeopend[self::OPENING_INSCHRIJVING]) &&
            time() < strtotime($instellingGesloten[self::SLUITING_INSCHRIJVING_JURYLEDEN]))
        ) {
            return true;
        } else {
            return false;
        }
    }

    protected function uploadenVloermuziekToegestaan()
    {
        return false;
        //todo: deze functie schrijven
    }

    protected function factuurBekijkenToegestaan()
    {
        return false;
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
    protected function createVoorinschrijvingToken($email, $tokenObject = null)
    {
        $token = sha1(mt_rand());
        if ($tokenObject === null) {
            $tokenObject = new Voorinschrijving();
        }
        $tokenObject->setToken($token);
        $tokenObject->setCreatedAt(new \DateTime('now'));
        $tokenObject->setTokenSentTo($email);

        $this->addToDB($tokenObject);

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
