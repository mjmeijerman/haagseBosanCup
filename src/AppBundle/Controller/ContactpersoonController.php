<?php

namespace AppBundle\Controller;

use AppBundle\Entity\FileUpload;
use AppBundle\Entity\FotoUpload;
use AppBundle\Entity\Jurylid;
use AppBundle\Entity\Nieuwsbericht;
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
}
