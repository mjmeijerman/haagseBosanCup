<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Betaling;
use AppBundle\Entity\Instellingen;
use AppBundle\Entity\Reglementen;
use AppBundle\Entity\Turnster;
use AppBundle\Entity\User;
use AppBundle\Entity\Voorinschrijving;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use Symfony\Component\Validator\Constraints\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank as EmptyConstraint;

/**
 * @Security("has_role('ROLE_ORGANISATIE')")
 */
class OrganisatieController extends BaseController
{

    /**
     * @Route("/organisatie/{page}/", name="organisatieGetContent", defaults={"page" = "Mijn gegevens"})
     * @Method("GET")
     */
    public function getOrganisatiePage($page)
    {
        $this->updateGereserveerdePlekken();
        $this->setBasicPageData('Organisatie');
        switch ($page) {
            case 'Home':
                return $this->getOrganisatieHomePage();
            case 'To-do lijst':
                return $this->getOrganisatieGegevensPage();
            case 'Instellingen':
                return $this->getOrganisatieInstellingenPage();
            case 'Mails':
                return $this->getOrganisatieGegevensPage();
            case 'Inschrijvingen':
                return $this->getOrganisatieInschrijvingenPage();
            case 'Juryzaken':
                return $this->getOrganisatieGegevensPage();
            case 'Financieel':
                return $this->getOrganisatieFacturenPage();
            case 'Mijn gegevens':
                return $this->getOrganisatieGegevensPage();
            case 'Vloermuziek':
                return $this->getOrganisatieGegevensPage();
        }
    }

    /**
     * @Route("/organisatie/removeContactpersoon/{id}/", name="removeContactpersoon")
     * @Method({"GET", "POST"})
     */
    public function removeContactpersoon(Request $request, $id)
    {
        $this->setBasicPageData('Organisatie');
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneBy(
                array('id' => $id)
            );
        if ($result) {
            if ($request->getMethod() == 'POST') {
                if ($request->request->get('bevestig')) {
                    $this->removeFromDB($result);
                    return $this->redirectToRoute('organisatieGetContent', ['page' => 'Inschrijvingen']);
                }
            }
            $contactpersoon = [
                'naam' => $result->getVoornaam() . ' ' . $result->getAchternaam(),
                'vereniging' => $result->getVereniging()->getNaam() . ', ' . $result->getVereniging()->getPlaats(),
            ];
            return $this->render('organisatie/removeContactpersoon.html.twig', array(
                'menuItems' => $this->menuItems,
                'contactpersoon' => $contactpersoon,
                'totaalAantalVerenigingen' => $this->aantalVerenigingen,
                'totaalAantalTurnsters' => $this->aantalTurnsters,
                'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
                'totaalAantalJuryleden' => $this->aantalJury,
            ));
        }
        return $this->redirectToRoute('organisatieGetContent', ['page' => 'Inschrijvingen']);
    }

    /**
     * @Template()
     * @Route("/organisatie/{page}/uploadReglementen/", name="addReglementen")
     * @Method({"GET", "POST"})
     */
    public function addAdminFileAction(Request $request, $page)
    {
        $this->setBasicPageData('Organisatie');
        $file = new Reglementen();
        $form = $this->createFormBuilder($file)
            ->add('naam')
            ->add('file')
            ->add('uploadBestand', 'submit')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $file->setUploader($user->getUsername());
            $file->setCreatedAt(new \DateTime('now'));
            $this->addToDB($file);
            return $this->redirectToRoute('organisatieGetContent', ['page' => $page]);
        }
        else {
            return $this->render('organisatie/reglementen.html.twig', array(
                'menuItems' => $this->menuItems,
                'form' => $form->createView(),
                'totaalAantalVerenigingen' => $this->aantalVerenigingen,
                'totaalAantalTurnsters' => $this->aantalTurnsters,
                'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
                'totaalAantalJuryleden' => $this->aantalJury,
            ));
        }
    }

    private function getGegevens()
    {
        $userObject = $this->getUser();
        return $userObject->getAll();
    }

    private function getOrganisatieGegevensPage()
    {
        $gegevens = $this->getGegevens();
        return $this->render('organisatie/organisatieGegevens.html.twig', array(
            'menuItems' => $this->menuItems,
            'gegevens' => $gegevens,
            'totaalAantalVerenigingen' => $this->aantalVerenigingen,
            'totaalAantalTurnsters' => $this->aantalTurnsters,
            'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
            'totaalAantalJuryleden' => $this->aantalJury,
        ));
    }

    /**
     * @Route("/organisatie/editInstellingen/{fieldName}/{data}/", name="editInstellingen", options={"expose"=true})
     * @Method("GET")
     */
    public function editInstellingen($fieldName, $data)
    {
        $fieldName = str_replace('_', ' ', $fieldName);
        $returnData['error'] = null;
        $result = $this->getOrganisatieInstellingen($fieldName);
        $returnData['data'] = $result[$fieldName];
        if ($data == 'null') {
            return new JsonResponse($returnData);
        }
        $instellingen = new Instellingen();
        switch ($fieldName) {
            case self::MAX_AANTAL_TURNSTERS:
                try {
                    $instellingen->setInstelling($fieldName);
                    $instellingen->setGewijzigd(new \DateTime('now'));
                    $instellingen->setAantal($data);
                    $this->addToDB($instellingen);
                    $result = $this->getOrganisatieInstellingen($fieldName);
                    $returnData['data'] = $result[$fieldName];
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            default:
                try {
                    $instellingen->setInstelling($fieldName);
                    $instellingen->setGewijzigd(new \DateTime('now'));
                    $instellingen->setDatum(new \DateTime($data));
                    $this->addToDB($instellingen);
                    $result = $this->getOrganisatieInstellingen($fieldName);
                    $returnData['data'] = $result[$fieldName];
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
        }
        return new JsonResponse($returnData);
    }

    /**
     * @Route("/organisatie/{page}/genereerVoorinschrijving/", name="genereerVoorinschrijving")
     * @Method({"GET", "POST"})
     */
    public function genereerVoorinschrijving(Request $request, $page)
    {
        if ($request->request->get('email')) {
            $this->createVoorinschrijvingToken($request->request->get('email'));
            $this->addFlash(
                'success',
                'Een voorinschrijvingslink is gemaild'
            );
            return $this->redirectToRoute('organisatieGetContent', ['page' => $page]);
        } else {
            $this->setBasicPageData('Organisatie');
            return $this->render('organisatie/genereerVoorinschrijving.html.twig', array(
                'menuItems' => $this->menuItems,
                'totaalAantalVerenigingen' => $this->aantalVerenigingen,
                'totaalAantalTurnsters' => $this->aantalTurnsters,
                'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
                'totaalAantalJuryleden' => $this->aantalJury,
            ));
        }
    }

    private function removeVoorinschrijving($id)
    {
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findOneBy(
                array('id' => $id)
            );
        if ($result) {
            $this->removeFromDB($result);
        }
    }

    /**
     * @Route("/organisatie/{page}/removeVoorinschrijving/{id}", name="removeVoorinschrijving")
     * @Method({"GET"})
     */
    public function removeVoorinschrijvingsPage($page, $id)
    {
        $this->removeVoorinschrijving($id);
        $this->addFlash(
            'success',
            'De link is verwijderd'
        );
        return $this->redirectToRoute('organisatieGetContent', ['page' => $page]);
    }

    private function refreshVoorinschrijving($id)
    {
        /** @var Voorinschrijving $result */
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findOneBy(
                array('id' => $id)
            );
        if ($result) {
            $this->createVoorinschrijvingToken($result->getTokenSentTo(), $result);
            $this->addFlash(
                'success',
                'Een nieuwe voorinschrijvingslink is gemaild'
            );
        }
    }

    /**
     * @Route("/organisatie/{page}/refreshVoorinschrijving/{id}", name="refreshVoorinschrijving")
     * @Method({"GET"})
     */
    public function refreshVoorinschrijvingsPage($page, $id)
    {
        $this->refreshVoorinschrijving($id);
        return $this->redirectToRoute('organisatieGetContent', ['page' => $page]);
    }

    private function getVoorinschrijvingen()
    {
        /** @var Voorinschrijving[] $results */
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findBy(
                [],
                ['createdAt' => 'DESC']
            );
        $voorinschrijvingen = [];
        foreach ($results as $result) {
            $voorinschrijvingen[] = $result->getAll();
        }
        return $voorinschrijvingen;
    }

    private function getReglementen()
    {
        /** @var Reglementen[] $result */
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Reglementen')
            ->findBy(
                [],
                ['id' => 'DESC']
            );
        if ($result) {
            $reglementen = $result[0]->getAll();
        } else {
            $reglementen = [
                'id' => 0,
                'naam' => '',
                'locatie' => '',
                'createdAt' => '',
            ];
        }
        return $reglementen;
    }

    private function getOrganisatieInstellingenPage($successMessage = false)
    {
        $instellingen = $this->getOrganisatieInstellingen();
        $voorinschrijvingen = $this->getVoorinschrijvingen();
        $reglementen = $this->getReglementen();
        return $this->render('organisatie/organisatieInstellingen.html.twig', array(
            'menuItems' => $this->menuItems,
            'instellingen' => $instellingen,
            'voorinschrijvingen' => $voorinschrijvingen,
            'reglementen' => $reglementen,
            'successMessage' => $successMessage,
            'totaalAantalVerenigingen' => $this->aantalVerenigingen,
            'totaalAantalTurnsters' => $this->aantalTurnsters,
            'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
            'totaalAantalJuryleden' => $this->aantalJury,
        ));
    }

    private function getOrganisatieHomePage()
    {
        return $this->render('organisatie/organisatieIndex.html.twig', array(
            'menuItems' => $this->menuItems,
            'totaalAantalVerenigingen' => $this->aantalVerenigingen,
            'totaalAantalTurnsters' => $this->aantalTurnsters,
            'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
            'totaalAantalJuryleden' => $this->aantalJury,
        ));
    }

    private function getContactpersonen()
    {
        /** @var User[] $results */
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->loadUsersByRole('ROLE_CONTACT');
        $contactpersonen = [];
        foreach ($results as $result) {
            /** @var Turnster[] $turnsters */
            $turnsters = $result->getTurnster();
            $turnstersGeplaatst = [];
            $turnstersWachtlijst = [];
            foreach ($turnsters as $turnster) {
                if ($turnster->getAfgemeld()) continue;
                if ($turnster->getWachtlijst()) {
                    $turnstersWachtlijst[] = $turnster;
                } else {
                    $turnstersGeplaatst[] = $turnster;
                }
            }
            $juryleden = $result->getJurylid();
            $contactpersonen[] = [
                'id' => $result->getId(),
                'naam' => $result->getVoornaam() . ' ' . $result->getAchternaam(),
                'vereniging' => $result->getVereniging()->getNaam() . ', ' . $result->getVereniging()->getPlaats(),
                'turnstersGeplaatst' => count($turnstersGeplaatst),
                'turnstersWachtlijst' => count($turnstersWachtlijst),
                'aantalJuryleden' => count($juryleden),
            ];
        }
        return $contactpersonen;
    }

    private function getAantallenPerNiveau($groepen)
    {
        $aantallenPerNiveau = [];
        $aantallenPerNiveau['geplaatst'] = [];
        $aantallenPerNiveau['wachtlijst'] = [];
        foreach ($groepen as $categorie => $niveaus) {
            $aantallenPerNiveau['geplaatst'][$categorie] = [];
            $aantallenPerNiveau['wachtlijst'][$categorie] = [];
            foreach ($niveaus as $niveau) {
                $geboortejaren = $this->getGeboortejaarFromCategorie($categorie);
                if (is_array($geboortejaren)) {
                    $aantallenPerNiveau['geplaatst'][$categorie][$niveau] = 0;
                    $aantallenPerNiveau['wachtlijst'][$categorie][$niveau] = 0;
                    foreach ($geboortejaren as $geboortejaar) {
                        $aantallenPerNiveau['geplaatst'][$categorie][$niveau] += $this->getDoctrine()->getRepository
                        ('AppBundle:Turnster')
                            ->getAantalTurnstersPerNiveau($geboortejaar, $niveau);
                        $aantallenPerNiveau['wachtlijst'][$categorie][$niveau] += $this->getDoctrine()->getRepository
                        ('AppBundle:Turnster')
                            ->getAantalTurnstersWachtlijstPerNiveau($geboortejaar, $niveau);
                    }
                } else {
                    $aantallenPerNiveau['geplaatst'][$categorie][$niveau] = $this->getDoctrine()->getRepository
                    ('AppBundle:Turnster')
                        ->getAantalTurnstersPerNiveau($geboortejaren, $niveau);
                    $aantallenPerNiveau['wachtlijst'][$categorie][$niveau] = $this->getDoctrine()->getRepository
                    ('AppBundle:Turnster')
                        ->getAantalTurnstersWachtlijstPerNiveau($geboortejaren, $niveau);
                }
            }
        }
        return $aantallenPerNiveau;
    }

    private function getOrganisatieFacturenPage()
    {
        /** @var User[] $results */
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->loadUsersByRole('ROLE_CONTACT');
        $factuurInformatie = [];
        foreach ($results as $result) {
            $factuurNummer = 'HBC' . date('Y', time()) . '-' . $result->getId();
            $bedragPerTurnster = 15; //todo: bedrag per turnster toevoegen aan instellingen
            $juryBoeteBedrag = 35; //todo: boete bedrag jury tekort toevoegen aan instellingen
            $jurylidPerAantalTurnsters = 10; //todo: toevoegen als instelling
            $juryledenAantal = $this->getDoctrine()
                ->getRepository('AppBundle:Jurylid')
                ->getIngeschrevenJuryleden($result);
            $turnstersAantal = $this->getDoctrine()
                ->getRepository('AppBundle:Turnster')
                ->getIngeschrevenTurnsters($result);
            $turnstersAfgemeldAantal = $this->getDoctrine()
                ->getRepository('AppBundle:Turnster')
                ->getAantalAfgemeldeTurnsters($result);

            $teLeverenJuryleden = ceil($turnstersAantal / $jurylidPerAantalTurnsters);
            if (($juryTekort = $teLeverenJuryleden - $juryledenAantal) < 0) {
                $juryTekort = 0;
            }
            $teBetalenBedrag = ($turnstersAantal + $turnstersAfgemeldAantal) * $bedragPerTurnster + $juryTekort *
                $juryBoeteBedrag;

            /** @var Betaling[] $betalingen */
            $betalingen = $result->getBetaling();
            $betaaldBedrag = 0;
            if (count($betalingen) == 0) {
                $voldaanClass = 'niet_voldaan';
                $status = 'Niet voldaan';
            } else {
                foreach ($betalingen as $betaling) {
                    $betaaldBedrag += $betaling->getBedrag();
                } if ($betaaldBedrag < $teBetalenBedrag) {
                    $voldaanClass = 'bijna_voldaan';
                    $status = 'Gedeeltelijk voldaan';
                } else {
                    $voldaanClass = 'voldaan';
                    $status = 'Voldaan';
                }
            }

            $factuurInformatie[] = [
                'vereniging' => $result->getVereniging()->getNaam() . ' ' . $result->getVereniging()->getPlaats(),
                'factuurNr' => $factuurNummer,
                'bedrag' => $teBetalenBedrag,
                'status' => $status,
                'voldaanClass' => $voldaanClass,
                'openstaandBedrag' => $teBetalenBedrag - $betaaldBedrag,
                'aantalTurnsters' => $turnstersAantal,
                'aantalAfgemeld' => $turnstersAfgemeldAantal,
                'juryTekort' => $juryTekort,
            ];
        }
        return $this->render('organisatie/organisatieFinancieel.html.twig', array(
            'menuItems' => $this->menuItems,
            'totaalAantalVerenigingen' => $this->aantalVerenigingen,
            'totaalAantalTurnsters' => $this->aantalTurnsters,
            'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
            'totaalAantalJuryleden' => $this->aantalJury,
            'factuurInformatie' => $factuurInformatie,
        ));
    }

    private function getOrganisatieInschrijvingenPage()
    {
        $groepen = $this->getGroepen();
        $aantallenPerNiveau = $this->getAantallenPerNiveau($groepen);
        $contactpersonen = $this->getContactpersonen();
        return $this->render('organisatie/organisatieInschrijvingen.html.twig', array(
            'menuItems' => $this->menuItems,
            'contactpersonen' => $contactpersonen,
            'totaalAantalVerenigingen' => $this->aantalVerenigingen,
            'totaalAantalTurnsters' => $this->aantalTurnsters,
            'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
            'totaalAantalJuryleden' => $this->aantalJury,
            'groepen' => $groepen,
            'aantallenPerNiveau' => $aantallenPerNiveau,
        ));
    }

    /**
     * @Route("/organisatie/{page}/editPassword/", name="editPassword")
     * @Method({"GET", "POST"})
     */
    public function editPassword(Request $request, $page)
    {
        if ($page == 'Mijn gegevens') {
            $error = false;
            if ($request->getMethod() == 'POST') {
                if ($request->request->get('pass1') != $request->request->get('pass2')) {
                    $this->addFlash(
                        'error',
                        'De wachtwoorden zijn niet gelijk'
                    );
                    $error = true;
                }
                if (strlen($request->request->get('pass1')) < 6) {
                    $this->addFlash(
                        'error',
                        'Het wachtwoord moet minimaal 6 karakters bevatten'
                    );
                    $error = true;
                }
                if (strlen($request->request->get('pass1')) > 20) {
                    $this->addFlash(
                        'error',
                        'Het wachtwoord mag maximaal 20 karakters bevatten'
                    );
                    $error = true;
                }
                if (!($error)) {
                    $userObject = $this->getUser();
                    $password = $request->request->get('pass1');
                    $encoder = $this->container
                        ->get('security.encoder_factory')
                        ->getEncoder($userObject);
                    $userObject->setPassword($encoder->encodePassword($password, $userObject->getSalt()));
                    $this->addToDB($userObject);
                    $this->addFlash(
                        'success',
                        'Het wachtwoord is succesvol gewijzigd'
                    );
                    return $this->redirectToRoute('organisatieGetContent', array(
                        'page' => $page,
                    ));
                }
            }
            $this->setBasicPageData();
            return $this->render('organisatie/editPassword.html.twig', array(
                'menuItems' => $this->menuItems,
                'totaalAantalVerenigingen' => $this->aantalVerenigingen,
                'totaalAantalTurnsters' => $this->aantalTurnsters,
                'totaalAantalTurnstersWachtlijst' => $this->aantalWachtlijst,
                'totaalAantalJuryleden' => $this->aantalJury,
            ));
        }
    }

}
