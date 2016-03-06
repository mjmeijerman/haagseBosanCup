<?php

namespace AppBundle\Controller;

use AppBundle\Entity\FileUpload;
use AppBundle\Entity\FotoUpload;
use AppBundle\Entity\Jurylid;
use AppBundle\Entity\Nieuwsbericht;
use AppBundle\Entity\Scores;
use AppBundle\Entity\Sponsor;
use AppBundle\Entity\Turnster;
use AppBundle\Entity\User;
use AppBundle\Form\Type\EditSponsorType;
use AppBundle\Form\Type\NieuwsberichtType;
use AppBundle\Form\Type\OrganisatieType;
use AppBundle\Form\Type\SponsorType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use AppBundle\Form\Type\ContentType;
use Symfony\Component\Validator\Constraints\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

/**
 * @Security("has_role('ROLE_CONTACT')")
 */
class ContactpersoonController extends BaseController
{
    /**
     * @Route("/contactpersoon/", name="getContactpersoonIndexPage")
     * @Method("GET")
     */
    public function getIndexPageAction()
    {
        $this->setBasicPageData();
        /** @var User $user */
        $user = $this->getUser();
        $contactgevens = [
            'vereniging' => $user->getVereniging()->getNaam() . ', ' . $user->getVereniging()->getPlaats(),
            'gebruikersnaam' => $user->getUsername(),
            'voornaam' => $user->getVoornaam(),
            'achternaam' => $user->getAchternaam(),
            'email' => $user->getEmail(),
        ];
        $turnsters = [];
        $wachtlijst = [];
        $afgemeld = [];
        /** @var Turnster[] $turnsterObjecten */
        $turnsterObjecten = $user->getTurnster();
        foreach ($turnsterObjecten as $turnsterObject) {
            if ($turnsterObject->getVloermuziek()) {
                $locatie = $turnsterObject->getVloermuziek()->getLocatie();
            } else {
                $locatie = '';
            }
            if ($turnsterObject->getAfgemeld()) {
                $afgemeld[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                    'opmerking' => $turnsterObject->getOpmerking(),
                ];
            } elseif ($turnsterObject->getWachtlijst()) {
                $wachtlijst[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                    'vloermuziek' => $locatie,
                    'opmerking' => $turnsterObject->getOpmerking(),
                ];
            }  else {
                $turnsters[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                    'wedstrijdnummer' => $turnsterObject->getScores()->getWedstrijdnummer(),
                    'vloermuziek' => $locatie,
                    'opmerking' => $turnsterObject->getOpmerking(),
                ];
            }
        }
        $juryleden = [];
        /** @var Jurylid[] $juryObjecten */
        $juryObjecten = $user->getJurylid();
        foreach ($juryObjecten as $juryObject) {
            $juryleden[] = [
                'voornaam' => $juryObject->getVoornaam(),
                'achternaam' => $juryObject->getAchternaam(),
                'opmerking' => $juryObject->getOpmerking(),
                'brevet' => $juryObject->getBrevet(),
            ];
        }
        return $this->render('contactpersoon/contactpersoonIndex.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'contactgegevens' => $contactgevens,
            'turnsters' => $turnsters,
            'wachtlijstTurnsters' => $wachtlijst,
            'afgemeldTurnsters' => $afgemeld,
            'juryleden' => $juryleden,
        ));
    }

    /**
     * @Route("/contactpersoon/addTurnster/", name="addTurnster")
     * @Method({"GET", "POST"})
     */
    public function addTurnster(Request $request)
    {
        $this->setBasicPageData();
        $turnster = [
            'voornaam' => '',
            'achternaam' => '',
            'geboortejaar' => '',
            'niveau' => '',
            'opmerking' => '',
        ];
        $classNames = [
            'voornaam' => 'text',
            'achternaam' => 'text',
            'geboortejaar' => 'turnster_niveau',
            'niveau' => 'turnster_niveau',
            'opmerking' => 'text',
        ];
        $geboorteJaren = $this->getGeboorteJaren();
        $vrijePlekken = $this->getVrijePlekken();
        $csrfToken = $this->getToken();
        if ($request->getMethod() == 'POST') {
            $turnster = [
                'voornaam' => $request->request->get('voornaam'),
                'achternaam' => $request->request->get('achternaam'),
                'geboortejaar' => $request->request->get('geboorteJaar'),
                'niveau' => $request->request->get('niveau'),
                'opmerking' => $request->request->get('opmerking'),
            ];
            $postedToken = $request->request->get('csrfToken');
            if (!empty($postedToken)) {
                if ($this->isTokenValid($postedToken)) {
                    $validationTurnster = [
                        'voornaam' => false,
                        'achternaam' => false,
                        'geboorteJaar' => false,
                        'niveau' => false,
                        'opmerking' => true,
                    ];

                    $classNames['opmerking'] = 'succesIngevuld';

                    if (strlen($request->request->get('voornaam')) > 1) {
                        $validationTurnster['voornaam'] = true;
                        $classNames['voornaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige voornaam ingevoerd'
                        );
                        $classNames['voornaam'] = 'error';
                    }

                    if (strlen($request->request->get('achternaam')) > 1) {
                        $validationTurnster['achternaam'] = true;
                        $classNames['achternaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige achternaam ingevoerd'
                        );
                        $classNames['achternaam'] = 'error';
                    }
                    if ($request->request->get('geboorteJaar')) {
                        $validationTurnster['geboorteJaar'] = true;
                        $classNames['geboortejaar'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geboortejaar ingevoerd'
                        );
                        $classNames['geboortejaar'] = 'error';
                    }

                    if ($request->request->get('niveau')) {
                        $validationTurnster['niveau'] = true;
                        $classNames['niveau'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen niveau ingevoerd'
                        );
                        $classNames['niveau'] = 'error';
                    }
                    if (!(in_array(false, $validationTurnster))) {
                        $turnster = new Turnster();
                        $scores = new Scores();
                        if ($this->getVrijePlekken() > 0) {
                            $turnster->setWachtlijst(false);
                        } else {
                            $turnster->setWachtlijst(true);
                        }
                        $turnster->setCreationDate(new \DateTime('now'));
                        $turnster->setExpirationDate(null);
                        $turnster->setScores($scores);
                        $turnster->setUser($this->getUser());
                        $turnster->setIngevuld(true);
                        $turnster->setVoornaam($request->request->get('voornaam'));
                        $turnster->setAchternaam($request->request->get('achternaam'));
                        $turnster->setGeboortejaar($request->request->get('geboorteJaar'));
                        $turnster->setNiveau($request->request->get('niveau'));
                        $turnster->setOpmerking($request->request->get('opmerking'));
                        $this->getUser()->addTurnster($turnster);
                        $this->addToDB($this->getUser());
                        return $this->redirectToRoute('getContactpersoonIndexPage');
                    }
                }
            }
        }
        return $this->render('contactpersoon/addTurnster.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'vrijePlekken' => $vrijePlekken,
            'turnster' => $turnster,
            'geboorteJaren' => $geboorteJaren,
            'classNames' => $classNames,
            'csrfToken' => $csrfToken,
        ));
    }

    /**
     * @Route("/contactpersoon/addJury/", name="addJury")
     * @Method({"GET", "POST"})
     */
    public function addJury(Request $request)
    {
        $this->setBasicPageData();
        $jury = [
            'voornaam' => '',
            'achternaam' => '',
            'email' => '',
            'brevet' => '',
            'opmerking' => '',
            'dag' => '',
        ];
        $classNames = [
            'voornaam' => 'text',
            'achternaam' => 'text',
            'email' => 'text',
            'brevet' => 'turnster_niveau',
            'opmerking' => 'text',
            'dag' => 'turnster_niveau',
        ];
        $csrfToken = $this->getToken();
        if ($request->getMethod() == 'POST') {
            $jury = [
                'voornaam' => $request->request->get('voornaam'),
                'achternaam' => $request->request->get('achternaam'),
                'email' => $request->request->get('email'),
                'brevet' => $request->request->get('brevet'),
                'dag' => $request->request->get('dag'),
                'opmerking' => $request->request->get('opmerking'),
            ];
            $postedToken = $request->request->get('csrfToken');
            if (!empty($postedToken)) {
                if ($this->isTokenValid($postedToken)) {
                    $validationJury = [
                        'voornaam' => false,
                        'achternaam' => false,
                        'email' => false,
                        'brevet' => false,
                        'dag' => false,
                        'opmerking' => true,
                    ];

                    $classNames['opmerking'] = 'succesIngevuld';

                    if (strlen($request->request->get('voornaam')) > 1) {
                        $validationJury['voornaam'] = true;
                        $classNames['voornaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige voornaam ingevoerd'
                        );
                        $classNames['voornaam'] = 'error';
                    }

                    if (strlen($request->request->get('achternaam')) > 1) {
                        $validationJury['achternaam'] = true;
                        $classNames['achternaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige achternaam ingevoerd'
                        );
                        $classNames['achternaam'] = 'error';
                    }

                    if (strlen($request->request->get('email')) > 1) {
                        $emailConstraint = new EmailConstraint();
                        $errors = $this->get('validator')->validate(
                            $request->request->get('email'),
                            $emailConstraint
                        );
                        if (count($errors) == 0) {
                            $validationJury['email'] = true;
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
                    } else {
                        $this->addFlash(
                            'error',
                            'geen email ingevoerd'
                        );
                        $classNames['email'] = 'error';
                    }

                    if ($request->request->get('brevet')) {
                        $validationJury['brevet'] = true;
                        $classNames['brevet'] = 'brevet';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen brevet ingevoerd'
                        );
                        $classNames['brevet'] = 'error';
                    }

                    if ($request->request->get('dag')) {
                        $validationJury['dag'] = true;
                        $classNames['dag'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen dag ingevoerd'
                        );
                        $classNames['dag'] = 'error';
                    }
                    if (!(in_array(false, $validationJury))) {
                        $jurylid = new Jurylid();
                        $jurylid->setVoornaam($request->request->get('voornaam'));
                        $jurylid->setAchternaam($request->request->get('achternaam'));
                        $jurylid->setEmail($request->request->get('email'));
                        $jurylid->setBrevet($request->request->get('brevet'));
                        $jurylid->setOpmerking($request->request->get('opmerking'));
                        if ($request->request->get('dag') == 'za') {
                            $jurylid->setZaterdag(true);
                            $jurylid->setZondag(false);
                        } elseif ($request->request->get('dag') == 'zo') {
                            $jurylid->setZaterdag(false);
                            $jurylid->setZondag(true);
                        } else {
                            $jurylid->setZaterdag(true);
                            $jurylid->setZondag(true);
                        }
                        $jurylid->setUser($this->getUser());
                        $this->getUser()->addJurylid($jurylid);
                        $this->addToDB($this->getUser());
                        return $this->redirectToRoute('getContactpersoonIndexPage');
                    }
                }
            }
        }
        return $this->render('contactpersoon/addJury.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'jury' => $jury,
            'classNames' => $classNames,
            'csrfToken' => $csrfToken,
        ));
    }
}
