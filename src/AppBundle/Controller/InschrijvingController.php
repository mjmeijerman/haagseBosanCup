<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\Vereniging;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;


class InschrijvingController extends BaseController
{

    /**
     * @Route("/inschrijven", name="inschrijven")
     * @Method({"GET", "POST"})
     */
    public function inschrijvenPage(Request $request)
    {
        if ($this->inschrijvingToegestaan($request->query->get('token'))) {
            $display = "none";
            $verenigingOption = '';
            $values = [
                'verenigingId' => '',
                'verenigingsnaam' => '',
                'verenigingsplaats' => '',
                'voornaam' => '',
                'achternaam' => '',
                'email' => '',
                'telefoonnummer' => '',
                'username' => '',
                'wachtwoord' => '',
                'wachtwoord2' => '',
                'aantalTurnsters' => '',
            ];
            $classNames = [
                'verenigingnaam' => 'select',
                'verenigingsnaam' => 'text',
                'verenigingsplaats' => 'text',
                'voornaam' => 'text',
                'achternaam' => 'text',
                'email' => 'text',
                'telefoonnummer' => 'text',
                'username' => 'text',
                'wachtwoord' => 'text',
                'wachtwoord2' => 'text',
                'aantalTurnsters' => 'number',
                'inschrijven_vereniging_header' => '',
                'inschrijven_contactpersoon_header' => '',
                'aantal_plekken_header' => '',
            ];
            $this->setBasicPageData();
            if ($request->getMethod() == 'POST') {
                $display = "";
                if ($request->request->get('verenigingsid')) {
                    $values['verenigingId'] = $request->request->get('verenigingsid');
                } else {
                    $values['verenigingsnaam'] = $request->request->get('verenigingsnaam');
                    $values['verenigingsplaats'] = $request->request->get('verenigingsplaats');;
                    $verenigingOption = 'checked';
                }
                $values['voornaam'] = $request->request->get('voornaam');
                $values['achternaam'] = $request->request->get('achternaam');
                $values['email'] = $request->request->get('email');
                $values['telefoonnummer'] = $request->request->get('telefoonnummer');
                $values['username'] = $request->request->get('username');
                $values['wachtwoord'] = $request->request->get('wachtwoord');
                $values['wachtwoord2'] = $request->request->get('wachtwoord2');
                $values['aantalTurnsters'] = $request->request->get('aantalTurnsters');
                $postedToken = $request->request->get('csrfToken');
                if (!empty($postedToken)) {
                    if ($this->isTokenValid($postedToken)) {
                        $validationVereniging = [
                            'verengingsId' => false,
                            'verenigingsnaam' => false,
                            'verenigingsplaats' => false,
                        ];

                        if ($request->request->get('verenigingsid')) {
                            $validationVereniging['verenigingsnaam'] = true;
                            $validationVereniging['verenigingsplaats'] = true;
                            if ($this->getDoctrine()->getRepository('AppBundle:Vereniging')
                                ->findOneBy(['id' => $request->request->get('verenigingsid')])
                            ) {
                                $validationVereniging['verengingsId'] = true;
                                $classNames['verenigingnaam'] = 'selectIngevuld';
                            } else {
                                $this->addFlash(
                                    'error',
                                    'geen geldige vereniging geselecteerd'
                                );
                                $classNames['verenigingnaam'] = 'error';
                            }
                        } else {
                            $validationVereniging['verengingsId'] = true;
                            if (strlen($request->request->get('verenigingsnaam')) > 1) {
                                $validationVereniging['verenigingsnaam'] = true;
                                $classNames['verenigingsnaam'] = 'succesIngevuld';
                            } else {
                                $this->addFlash(
                                    'error',
                                    'geen geldige verenigingsnaam ingevoerd'
                                );
                                $classNames['verenigingsnaam'] = 'error';
                            }
                            if (strlen($request->request->get('verenigingsplaats')) > 1) {
                                $validationVereniging['verenigingsplaats'] = true;
                                $classNames['verenigingsplaats'] = 'succesIngevuld';
                            } else {
                                $this->addFlash(
                                    'error',
                                    'geen geldige verenigingsplaats ingevoerd'
                                );
                                $classNames['verenigingsplaats'] = 'error';
                            }
                        }
                        if (!(in_array(false, $validationVereniging))) {
                            $classNames['inschrijven_vereniging_header'] = 'success';
                        }

                        $validationContactpersoon = [
                            'voornaam' => false,
                            'achternaam' => false,
                            'email' => false,
                            'telefoonnummer' => false,
                            'username' => false,
                            'wachtwoord' => false,
                            'wachtwoord2' => false,
                        ];

                        if (strlen($request->request->get('voornaam')) > 1) {
                            $validationContactpersoon['voornaam'] = true;
                            $classNames['voornaam'] = 'succesIngevuld';
                        } else {
                            $this->addFlash(
                                'error',
                                'geen geldige voornaam ingevoerd'
                            );
                            $classNames['voornaam'] = 'error';
                        }

                        if (strlen($request->request->get('achternaam')) > 1) {
                            $validationContactpersoon['achternaam'] = true;
                            $classNames['achternaam'] = 'succesIngevuld';
                        } else {
                            $this->addFlash(
                                'error',
                                'geen geldige achternaam ingevoerd'
                            );
                            $classNames['achternaam'] = 'error';
                        }

                        $emailConstraint = new EmailConstraint();
                        $errors = $this->get('validator')->validate(
                            $request->request->get('email'),
                            $emailConstraint
                        );
                        if (count($errors) == 0) {
                            $validationContactpersoon['email'] = true;
                            $classNames['email'] = 'succesIngevuld';
                        } else {
                            foreach ($errors as $error) {
                                $this->addFlash(
                                    'error',
                                    $error->getMessage()
                                );
                            }
                            $classNames['email'] = 'error';
                        }

                        $re = '/^([0-9]+)$/';
                        if (preg_match($re,
                                $request->request->get('telefoonnummer')) && strlen($request->request->get('telefoonnummer')) == 10
                        ) {
                            $validationContactpersoon['telefoonnummer'] = true;
                            $classNames['telefoonnummer'] = 'succesIngevuld';
                        } else {
                            $this->addFlash(
                                'error',
                                'Het telefoonnummer moet uit precies 10 cijfers bestaan'
                            );
                            $classNames['telefoonnummer'] = 'error';
                        }

                        if (strlen($request->request->get('username')) > 1) {
                            if ($this->checkUsernameAvailability($request->request->get('username')) === 'true') {
                                $validationContactpersoon['username'] = true;
                                $classNames['username'] = 'succesIngevuld';
                            } else {
                                $this->addFlash(
                                    'error',
                                    'De inlognaam is al in gebruik'
                                );
                                $classNames['username'] = 'error';
                            }
                        } else {
                            $this->addFlash(
                                'error',
                                'Geen geldige inlognaam ingevoerd'
                            );
                            $classNames['username'] = 'error';
                        }

                        if (strlen($request->request->get('wachtwoord')) > 5) {
                            $validationContactpersoon['wachtwoord'] = true;
                            $classNames['wachtwoord'] = 'succesIngevuld';
                            $validationContactpersoon['wachtwoord2'] = true;
                            $classNames['wachtwoord2'] = 'succesIngevuld';
                        } else {
                            $this->addFlash(
                                'error',
                                'Dit wachtwoord is te kort'
                            );
                            $classNames['wachtwoord'] = 'error';
                        }

                        if ($request->request->get('wachtwoord') != $request->request->get('wachtwoord2')) {
                            $validationContactpersoon['wachtwoordenGelijk'] = false;
                            $this->addFlash(
                                'error',
                                'De wachtwoorden zijn niet aan elkaar gelijk'
                            );
                            $classNames['wachtwoord'] = 'error';
                            $classNames['wachtwoord2'] = 'error';
                        }

                        if (!(in_array(false, $validationContactpersoon))) {
                            $classNames['inschrijven_contactpersoon_header'] = 'success';
                        }

                        $validationAantalturnsters = false;
                        if ($request->request->get('aantalTurnsters') > 0) {
                            $validationAantalturnsters = true;
                            $classNames['aantalTurnsters'] = 'succesIngevuld';
                            $classNames['aantal_plekken_header'] = 'success';
                        } else {
                            $this->addFlash(
                                'error',
                                'Aantal turnsters moet groter zijn dan 0!'
                            );
                        }

                        if (!(in_array(false, $validationVereniging)) && !(in_array(false,
                                $validationContactpersoon)) &&
                            $validationAantalturnsters
                        ) {
                            //todo: safe and return ingevulde data
                            //todo: als alles correct is, reserveer plaatsen, update token, redirect to inschrijven_turnsters
                        }
                    }
                }
            }
            $vrijePlekken = $this->getVrijePlekken();
            $verenigingen = $this->getVerenigingen();
            $csrfToken = $this->getToken();
            return $this->render('inschrijven/inschrijven_contactpersoon.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' => $this->sponsors,
                'vrijePlekken' => $vrijePlekken,
                'verenigingen' => $verenigingen,
                'csrfToken' => $csrfToken,
                'display' => $display,
                'verenigingOption' => $verenigingOption,
                'classNames' => $classNames,
                'values' => $values,
            ));
            // todo: return inschrijvingspagina
        } else {
            return $this->redirectToRoute('getContent', array('page' => 'Inschrijvingsinformatie'));
        }
    }

    private function getVerenigingen()
    {
        $verenigingen = [];
        /** @var Vereniging[] $results */
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Vereniging')
            ->findBy(
                [],
                ['naam' => 'ASC']
            );
        foreach ($results as $result) {
            $verenigingen[] = $result->getAll();
        }
        return $verenigingen;
    }

    /**
     * @Route("/checkUsername/{username}/", name="checkUsernameAvailabilityAjaxCall", options={"expose"=true})
     * @Method("GET")
     */
    public function checkUsernameAvailabilityAjaxCall($username)
    {
        return new Response($this->checkUsernameAvailability($username));
    }

    private function checkUsernameAvailability($username)
    {
        /** @var User[] $users */
        $users = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findAll();
        $usernames = [];
        foreach ($users as $user) {
            $usernames[] = strtolower($user->getUsername());
        }
        if (in_array(strtolower($username), $usernames)) {
            return 'false';
        } else {
            return 'true';
        }
    }
}
