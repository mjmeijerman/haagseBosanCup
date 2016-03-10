<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Instellingen;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;


class ContentController extends BaseController
{

    /**
     * @Route("/", name="getIndexPage")
     * @Method("GET")
     */
    public function indexAction()
    {
        return $this->redirectToRoute('getContent', array('page' => 'Laatste nieuws'));
    }

    /**
     * @Route("/pagina/{page}", name="getContent")
     * @Method("GET")
     */
    public function getContentAction($page)
    {
        $this->setBasicPageData();
        if ($this->checkIfPageExists($page)) {
            switch ($page) {
                case 'Inloggen':
                    return $this->getInloggenPageAction();
                    break;
                case 'Laatste nieuws':
                    return $this->getNieuwsIndexPage();
                case 'Sponsors':
                    return $this->render('default/sponsors.html.twig', array(
                        'menuItems' => $this->menuItems,
                        'sponsors' => $this->sponsors,
                    ));
                default:
                    $result = $this->getDoctrine()
                        ->getRepository('AppBundle:Content')
                        ->findBy(
                            array('pagina' => $page),
                            array('gewijzigd' => 'DESC')
                        );
                    if (count($result) > 0) {
                        /** @var Content $result */
                        $result = $result[0];
                    }
                    $content = "";
                    if ($result) {
                        $content = $result->getContent();
                    }
                    return $this->render('default/index.html.twig', array(
                        'content' => $content,
                        'menuItems' => $this->menuItems,
                        'sponsors' => $this->sponsors,
                    ));
            }
        } else {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' => $this->sponsors,
            ));
        }
    }

    private function getInloggenPageAction()
    {
        $user = $this->getUser();
        $roles[0] = "";
        if ($user) {
            $roles = $user->getRoles();
        }
        switch ($roles[0]) {
            case 'ROLE_ADMIN':
                return $this->redirectToRoute('getAdminIndexPage');
                break;
            case 'ROLE_CONTACT':
                return $this->redirectToRoute('getContactpersoonIndexPage');
                break;
            case 'ROLE_ORGANISATIE':
                return $this->redirectToRoute('organisatieGetContent', array('page' => 'Mijn gegevens'));
                break;
            default:
                return $this->redirectToRoute('login_route');
        }
    }

    /**
     * @Route("/getVrijePlekken/", name="aantalVrijePlekkenAjaxCall", options={"expose"=true})
     * @Method("GET")
     */
    public function aantalVrijePlekkenAjaxCall()
    {
        $this->updateGereserveerdePlekken();
        return new Response($this->getVrijePlekken());
    }

    private function getNieuwsIndexPage()
    {
        $aantalPlekken = -1;
        $tijdVol = false;
        $tijdTotVol = false;
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Nieuwsbericht')
            ->findBy(
                array(),
                array('id' => 'DESC'),
                10
            );
        if ($this->inschrijvingToegestaan()) {
            $aantalPlekken = $this->getVrijePlekken();
            if ($aantalPlekken == 0) {
                /** @var \DateTime $tijdVolObject */
                $tijdVolObject = $this->getTijdVol();
                $tijdVol['datum'] = $tijdVolObject->format('d-m-Y');
                $tijdVol['tijd'] = $tijdVolObject->format('H:i:s');
                $result = $this->getDoctrine()
                    ->getRepository('AppBundle:Instellingen')
                    ->findBy(
                        array('instelling' => self::OPENING_INSCHRIJVING),
                        array('gewijzigd' => 'DESC')
                    );
                $datumGeopend = 0;
                if (count($result) > 0) {
                    /** @var Instellingen[] $result */
                    /** @var \DateTime $datumGeopend */
                    $datumGeopend = $result[0]->getDatum();
                }
                $timestampVol = ($tijdVolObject->getTimestamp() - $datumGeopend->getTimestamp());
                $tijdTotVolDate = date('H:i:s', $timestampVol);
                $result = explode(':', $tijdTotVolDate);
                $tijdTotVol['uur'] = $result[0] - 1;
                $tijdTotVol['minuten'] = $result[1];
                $tijdTotVol['secondes'] = $result[2];
            }
        }
        $nieuwsItems = array();
        foreach ($results as $result) {
            $nieuwsItems[] = $result->getAll();
        }
        return $this->render('default/nieuws.html.twig', array(
            'nieuwsItems' => $nieuwsItems,
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'aantalPlekken' => $aantalPlekken,
            'tijdVol' => $tijdVol,
            'tijdTotVol' => $tijdTotVol,
        ));
    }

    /**
     * @Route("/inloggen/new_pass/", name="getNewPassPage")
     * @Method({"GET", "POST"})
     */
    public function getNewPassPageAction(Request $request)
    {
        $this->setBasicPageData();
        if ($request->getMethod() == 'POST') {
            $username = $this->get('request')->request->get('username');
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:User')
                ->loadUserByUsername($username);
            if (!$user) {
                $this->addFlash(
                    'error',
                    'Deze gebruikersnaam bestaat niet'
                );
            } else {
                $password = $this->generatePassword();
                $encoder = $this->container
                    ->get('security.encoder_factory')
                    ->getEncoder($user);
                $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
                $this->addToDB($user);
                $subject = 'Inloggegevens website Haagse Bosan Cup';
                $to = $user->getEmail();
                $view = 'mails/new_password.txt.twig';
                $mailParameters = array(
                    'username' => $user->getUsername(),
                    'password' => $password,
                );
                $this->sendEmail($subject, $to, $view, $mailParameters);
                $this->addFlash(
                    'success',
                    'Een nieuw wachtwoord is gemaild'
                );
                return $this->redirectToRoute('login_route');
            }
        }

        return $this->render('security/newPass.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
        ));
    }

    private function factuurHeader(AlphaPDFController $pdf, $factuurNummer)
    {
        //BACKGROUND
        $pdf->Image('images/background4.png', 0, 0);    //BACKGROUND2: 0,45		BACKGROUND3: 17,77

        //LOGO
        $pdf->SetFillColor(127);
        $pdf->Rect(0, 0, 210, 35, 'F');
        $pdf->Image('images/HBCFactuurheader.png');

        //FACTUUR, NUMMER EN DATUM
        $pdf->SetFont('Franklin', '', 16);
        $pdf->SetTextColor(255);
        $pdf->Text(5, 10, 'FACTUUR');
        $pdf->SetFontSize(10);
        $pdf->Text(6, 14, $factuurNummer);
        $datumFactuur = $this->getOrganisatieInstellingen(self::FACTUUR_BEKIJKEN_TOEGESTAAN);
        $pdf->Text(3, 32, 'Datum: ' . date('d-m-Y', strtotime($datumFactuur[self::FACTUUR_BEKIJKEN_TOEGESTAAN])));
        return $pdf;
    }

    //FOOTER
    private function factuurFooter(
        AlphaPDFController $pdf,
        $factuurNummer,
        $datumHBC,
        $locatieHBC,
        $rekeningNummer,
        $rekeningTNV
    ) {
        $pdf->SetX(3);
        $pdf->SetAlpha(0.6);
        $pdf->SetFont('Gotham', '', 8);
        $pdf->SetTextColor(0);

        //REKENINGNUMMER DETAILS
        $pdf->Text(3, 290, 'Haagse Bosan Cup - ' . $datumHBC . ', ' . $locatieHBC);
        $pdf->Text(3, 294, $rekeningNummer . ' - T.n.v. ' . $rekeningTNV . ' o.v.v. ' . $factuurNummer);

        //LOGO DONAR
        $pdf->Image('images/logodonarPNG.png', 188, 268);

        //LOGO HBC
        $pdf->Image('images/logohbcPNG.png', 8, 268);

        //DONAR SITE
        $pdf->Text(180, 290, 'www.donargym.nl');

        //HBC SITE
        $pdf->Text(171, 294, 'www.haagsebosancup.nl');
        return $pdf;
    }

    //ROUNDED RECTANGLE
    function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234', AlphaPDFController $pdf)
    {
        $k = $pdf->k;
        $hp = $pdf->h;
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' or $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $pdf->_out(sprintf('%.2f %.2f m', ($x + $r) * $k, ($hp - $y) * $k));

        $xc = $x + $w - $r;
        $yc = $y + $r;
        $pdf->_out(sprintf('%.2f %.2f l', $xc * $k, ($hp - $y) * $k));
        if (strpos($angle, '2') === false) {
            $pdf->_out(sprintf('%.2f %.2f l', ($x + $w) * $k, ($hp - $y) * $k));
        } else {
            $pdf = $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc, $pdf);
        }

        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $pdf->_out(sprintf('%.2f %.2f l', ($x + $w) * $k, ($hp - $yc) * $k));
        if (strpos($angle, '3') === false) {
            $pdf->_out(sprintf('%.2f %.2f l', ($x + $w) * $k, ($hp - ($y + $h)) * $k));
        } else {
            $pdf = $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r, $pdf);
        }

        $xc = $x + $r;
        $yc = $y + $h - $r;
        $pdf->_out(sprintf('%.2f %.2f l', $xc * $k, ($hp - ($y + $h)) * $k));
        if (strpos($angle, '4') === false) {
            $pdf->_out(sprintf('%.2f %.2f l', ($x) * $k, ($hp - ($y + $h)) * $k));
        } else {
            $pdf = $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc, $pdf);
        }

        $xc = $x + $r;
        $yc = $y + $r;
        $pdf->_out(sprintf('%.2f %.2f l', ($x) * $k, ($hp - $yc) * $k));
        if (strpos($angle, '1') === false) {
            $pdf->_out(sprintf('%.2f %.2f l', ($x) * $k, ($hp - $y) * $k));
            $pdf->_out(sprintf('%.2f %.2f l', ($x + $r) * $k, ($hp - $y) * $k));
        } else {
            $pdf = $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r, $pdf);
        }
        $pdf->_out($op);
        return $pdf;
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3, AlphaPDFController $pdf)
    {
        $h = $pdf->h;
        $pdf->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1 * $pdf->k, ($h - $y1) * $pdf->k,
            $x2 * $pdf->k, ($h - $y2) * $pdf->k, $x3 * $pdf->k, ($h - $y3) * $pdf->k));
        return $pdf;
    }

    /**
     * @Route("/contactpersoon/factuur/", name="pdfFactuur")
     * @Method("GET")
     */
    public function testPDFCreation($userId = null)
    {
        if ($this->factuurBekijkenToegestaan()) {
            if (!$this->getUser()) {
                return $this->redirectToRoute('getIndexPage');
            }
            if ($this->getUser()->getRole() == 'ROLE_ORGANISATIE' || $this->getUser()->getRole() == 'ROLE_CONTACT') {
                if ($this->getUser()->getRole() != 'ROLE_ORGANISATIE') {
                    $user = $this->getUser();
                } else {
                    $user = $this->getDoctrine()
                        ->getRepository('AppBundle:User')
                        ->findOneBy(['id' => $userId]);
                }
                $factuurNummer = 'HBC' . date('Y', time()) . '-' . $user->getId();
                $bedragPerTurnster = 15; //todo: bedrag per turnster toevoegen aan instellingen
                $juryBoeteBedrag = 35; //todo: boete bedrag jury tekort toevoegen aan instellingen
                $datumHBC = '4 & 5 juni 2016'; // todo: datum toernooi toevoegen aan instellingen
                $locatieHBC = 'Sporthal Overbosch'; //todo: locatie toernooi toevoegen aan instellingen
                $rekeningNummer = 'NL81 INGB 000 007 81 99'; // todo: rekeningnummer toevoegen aan instellingen
                $rekeningTNV = 'Gymnastiekver. Donar'; // todo: TNV toevoegen aan instellingen
                $jurylidPerAantalTurnsters = 10; //todo: toevoegen als instelling
                $juryledenAantal = $this->getDoctrine()
                    ->getRepository('AppBundle:Jurylid')
                    ->getIngeschrevenJuryleden($user);
                $turnstersAantal = $this->getDoctrine()
                    ->getRepository('AppBundle:Turnster')
                    ->getIngeschrevenTurnsters($user);
                $turnstersAfgemeldAantal = $this->getDoctrine()
                    ->getRepository('AppBundle:Turnster')
                    ->getAantalAfgemeldeTurnsters($user);

                $teLeverenJuryleden = ceil($turnstersAantal / $jurylidPerAantalTurnsters);
                if (($juryTekort = $teLeverenJuryleden - $juryledenAantal) < 0) {
                    $juryTekort = 0;
                }
                $teBetalenBedrag = ($turnstersAantal + $turnstersAfgemeldAantal) * $bedragPerTurnster + $juryTekort *
                    $juryBoeteBedrag;

                /** @var User $user */
                //START OF PDF
                $pdf = new AlphaPDFController();
                $pdf->SetMargins(0, 0);
                $pdf->AddFont('Gotham', '', 'Gotham-Light.php');
                $pdf->AddFont('Franklin', '', 'Frabk.php');
                $pdf->AddPage();

                $pdf = $this->factuurHeader($pdf, $factuurNummer);
                $pdf = $this->factuurFooter($pdf, $factuurNummer, $datumHBC, $locatieHBC, $rekeningNummer,
                    $rekeningTNV);

                //CONTACTPERSOON EN VERENIGING
                $pdf->SetFont('Franklin', '', 16);
                $pdf->SetTextColor(0);
                $pdf->SetFillColor(0);
                $pdf->Rect(5, 43, 0.5, 13, 'F');
                $pdf->Text(7, 48, $user->getVoornaam() . ' ' . $user->getAchternaam());
                $pdf->Text(7, 54, $user->getVereniging()->getNaam() . ' ' . $user->getVereniging()->getPlaats());

                //HR LINE
                $pdf->Rect(0, 63, 210, 0.3, 'F');

                //LINE BREAK
                $pdf->Ln(45);

                //FACTUURTABEL
                //EERSTE RIJ - HEADERS
                $pdf->Cell(20, 0);        //Blank space
                $pdf->SetFont('Gotham', '', 16);
                $pdf->Cell(97, 0, ' OMSCHRIJVING'); //De spatie voor OMSCHRIJVING hoort daar!
                $pdf->Cell(26, 0, 'AANTAL');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, 'BEDRAG');
                $pdf->Ln(8);
                //EURO-TEKENS
                $pdf->SetFont('Courier', '', 14);
                $pdf->Text(161, 89.9, EURO);
                $pdf->Text(161, 96.9, EURO);
                $pdf->Text(161, 103.9, EURO);
                $pdf->Text(161, 110.9, '');
                $pdf->SetFont('Gotham', '', 12);
                //TWEEDE RIJ - TURNSTERS
                $pdf->Cell(22, 0);        //Blank space
                $pdf->Cell(95, 0, 'Deelnemende turnsters');
                $pdf->Cell(26, 0, $turnstersAantal, 0, 0, 'C');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, ($turnstersAantal * $bedragPerTurnster), 0, 0, 'R');
                $pdf->Ln(7);
                //DERDE RIJ - AFGEMELDE TURNSTERS
                $pdf->Cell(22, 0);        //Blank space
                $pdf->Cell(95, 0, 'Afgemelde turnsters (na sluiting inschrijving)');
                $pdf->Cell(26, 0, $turnstersAfgemeldAantal, 0, 0, 'C');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, ($turnstersAfgemeldAantal * $bedragPerTurnster), 0, 0, 'R');
                $pdf->Ln(7);
                //VIERDE RIJ - JURYLEDEN TEKORT
                $pdf->Cell(22, 0);        //Blank space
                $pdf->Cell(95, 0, 'Tekort aan juryleden');
                $pdf->Cell(26, 0, $juryTekort, 0, 0, 'C');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, ($juryTekort * $juryBoeteBedrag), 0, 0, 'R');
                $pdf->Ln(7);
                //VIJFDE RIJ - ARRANGEMENT ZATERDAG
                $pdf->Cell(22, 0);        //Blank space
                $pdf->Cell(95, 0, '');
                $pdf->Cell(26, 0, '', 0, 0, 'C');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, '', 0, 0, 'R');
                $pdf->Ln(7);
                //TOTAALBEDRAG HR LINE
                $pdf->Rect(115, 116, 72, 0.2, 'F');
                $pdf->Ln(6);
                //ZESDE RIJ - TOTAALBEDRAG
                $pdf->SetAlpha(0.6);
                $pdf->SetFillColor(255, 255, 0);
                $pdf = $this->RoundedRect(115, 118.5, 72, 8, 2, 'F', 1234, $pdf);
                $pdf->SetAlpha(1);
                $pdf->SetFontSize(14);
                $pdf->Cell(22, 0);        //Blank space
                $pdf->Cell(95, 0);        //Blank space
                $pdf->Cell(26, 0, 'TOTAAL');
                $pdf->Cell(17, 0);        //Blank space
                $pdf->Cell(25, 0, $teBetalenBedrag, 0, 0, 'R');
                $pdf->Ln(7);
                //TOTAAL EURO-TEKEN
                $pdf->SetFont('Courier', '', 16);
                $pdf->Text(161, 123.9, EURO);
                $pdf->SetFont('Gotham', '', 12);

                //FILL COLOR BACK TO BLACK
                $pdf->SetFillColor(0);

                //HR LINE
                $pdf->Rect(0, 139, 210, 0.3, 'F');

                //LINE BREAK
                $pdf->Ln(16);

                //BETAALDETAILS
                $pdf->Cell(3, 35);
                $pdf->SetFontSize(12);
                $pdf->MultiCell(53, 5,
                    "Over te maken bedrag: \n Uiterste betaaldatum: \n \n Rekeningnummer: \n Ten name van: \n\n Factuurnummer:",
                    0, 'R');

                //EURO-TEKEN
                $pdf->SetFont('Courier', '', 13);
                $pdf->Text(57, 149.5, EURO);
                $pdf->SetFont('Gotham', '', 10);

                //BEDRAG
                $pdf->Text(61, 149.5, $teBetalenBedrag);

                //BETAALDATUM
                $uitersteBetaalDatum = $this->getOrganisatieInstellingen(self::UITERLIJKE_BETAALDATUM_FACTUUR);
                $pdf->Text(57, 154.5, date('d-m-Y', strtotime
                ($uitersteBetaalDatum[self::UITERLIJKE_BETAALDATUM_FACTUUR])));

                //REKENINGNUMMER
                $pdf->Text(57, 164.5, $rekeningNummer);

                //TNV
                $pdf->Text(57, 169.5, $rekeningTNV);

                //FACTUURNUMMER
                $pdf->Text(57, 179.5, $factuurNummer);

                //BETAALINSTRUCTIES
                //$pdf->SetFillColor(0,148,255); BLAUWE ACHTERGROND
                //ANDERE OPTIES: GELE ACHTERGROND

                $pdf->SetFillColor(0);
                $pdf->SetAlpha(0.5);
                $pdf = $this->RoundedRect(105.5, 144, 100, 38, 2, 'F', 1234, $pdf);
                $pdf->SetAlpha(1);

                $pdf->SetFontSize(14);
                $pdf->SetTextColor(255, 255, 0);
                $pdf->Text(130.5, 151, 'BETAALINSTRUCTIES');

                $pdf->SetTextColor(255);
                $pdf->SetFontSize(12);
                $pdf->Text(120, 158, 'Wij verzoeken u vriendelijk om het');
                $pdf->Text(116, 163, 'verschuldigde bedrag voor de uiterste');
                $pdf->Text(118, 168, 'betaaldatum over te maken naar het');
                $pdf->Text(109, 173, 'genoemde rekeningnummer. Vermeld bij het');
                $pdf->Text(116, 178, 'opmerkingenveld uw factuurnummer.');

                //DEFINITIEF NA BETALING
                $pdf->SetDrawColor(0);
                $pdf->SetTextColor(0);
                $pdf->Rect(4, 199, 202, 7, 'D');
                $pdf->Text(31, 204, 'Let op! Uw inschrijving is pas definitief zodra uw betaling is ontvangen.');

                //CONTACT BIJ PROBLEMEN
                $pdf->SetAlpha(0.6);
                $pdf->SetFontSize(8);
                $pdf->Text(34, 209,
                    'Mochten er zich problemen voordoen, neemt u dan alstublieft contact op via info@haagsebosancup.nl');
                return new Response($pdf->Output(), 200, array(
                    'Content-Type' => 'application/pdf'
                ));
            } else {
                return $this->redirectToRoute('getIndexPage');
            }
        } else {
            return $this->redirectToRoute('getIndexPage');
        }
    }
}
