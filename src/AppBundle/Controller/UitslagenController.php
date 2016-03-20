<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Instellingen;
use AppBundle\Entity\Turnster;
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


class UitslagenController extends BaseController
{
    private function getRanking($scores, $order = '')
    {
        $toestellen = ['Sprong', 'Brug', 'Balk', 'Vloer', ''];
        foreach ($toestellen as $toestel) {
            usort($scores, function ($a, $b) use ($toestel) {
                if ($a['totaal' . $toestel] == $b['totaal' . $toestel]) {
                    return 0;
                }
                return ($a['totaal' . $toestel] > $b['totaal' . $toestel]) ? -1 : 1;
            });
            for ($i = 1; $i <= count($scores); $i++) {
                if ($i == 1) {
                    $scores[($i - 1)]['rank' . $toestel] = $i;
                } elseif ($scores[($i - 1)]['totaal' . $toestel] == $scores[($i - 2)]['totaal' . $toestel]) {
                    $scores[($i - 1)]['rank' . $toestel] = $scores[($i - 2)]['rank' . $toestel];
                } else {
                    $scores[($i - 1)]['rank' . $toestel] = $i;
                }
            }
        }
        usort($scores, function ($a, $b) use ($order) {
            if ($a['totaal' . $order] == $b['totaal' . $order]) {
                return 0;
            }
            return ($a['totaal' . $order] > $b['totaal' . $order]) ? -1 : 1;
        });
        return $scores;
    }

    private function formatScoresForPrijswinnaars($turnsters)
    {
        $waardes = [];
        $toestellen = ['Sprong', 'Brug', 'Balk', 'Vloer', ''];
        $count = 0;
        foreach ($toestellen as $toestel) {
            usort($turnsters, function ($a, $b) use ($toestel) {
                if ($a['totaal' . $toestel] == $b['totaal' . $toestel]) {
                    return 0;
                }
                return ($a['totaal' . $toestel] > $b['totaal' . $toestel]) ? -1 : 1;
            });
            foreach ($turnsters as $turnster) {
                if ($turnster['rank' . $toestel] < 4) {
                    $waardes[$count][] = [
                        0 => $turnster['naam'],
                        1 => $turnster['vereniging'],
                        2 => $turnster['totaal' . $toestel],
                        3 => $turnster['rank' . $toestel],
                    ];
                } else {
                    break;
                }
            }
            $count++;
        }
        return $waardes;
    }

    private function uitslagenPdf(Request $request, $turnsters, $userId)
    {
        $pdf = new UitslagenPdfController('L', 'mm', 'A4');
        $pdf->setCategorie($request->query->get('categorie'));
        $pdf->setNiveau($request->query->get('niveau'));
        $pdf->SetLeftMargin(7);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->Table($turnsters, $userId);
        return new Response($pdf->Output(
            $request->query->get('categorie') . "_" . $request->query->get('niveau') . ".pdf", "I"
        ), 200, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    private function prijswinnaarsPdf(Request $request, $turnsters)
    {
        $waardes = $this->formatScoresForPrijswinnaars($turnsters);
        $pdf = new PrijswinnaarsPdfController('L', 'mm', 'A4');
        $pdf->setCategorie($request->query->get('categorie'));
        $pdf->setNiveau($request->query->get('niveau'));
        $pdf->SetLeftMargin(7);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->Table($waardes);
        return new Response($pdf->Output(), 200, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * @Route("/uitslagen/", name="uitslagen")
     * @Method("GET")
     */
    public function uitslagen(Request $request)
    {
        if ($request->query->get('categorie') && $request->query->get('niveau') && $this->checkIfNiveauToegestaan
            ($request->query->get('categorie'), $request->query->get('niveau'))
        ) {
            $userId = 0;
            if ($this->getUser()) {
                $userId = $this->getUser()->getId();
            }
            $order = 'totaal';
            if ($request->query->get('order')) {
                $order = $request->query->get('order');
            }
            /** @var Turnster[] $results */
            $results = $this->getDoctrine()->getRepository("AppBundle:Turnster")
                ->getIngeschrevenTurnstersCatNiveau($request->query->get('categorie'), $request->query->get('niveau'));
            $turnsters = [];
            foreach ($results as $result) {
                $turnsters[] = $result->getUitslagenLijst();
            }
            $turnsters = $this->getRanking($turnsters, $request->query->get('order'));
            if ($request->query->get('prijswinnaars')) {
                return $this->prijswinnaarsPdf($request, $turnsters);
            } elseif ($request->query->get('pdf')) {
                return $this->uitslagenPdf($request, $turnsters, $userId);
            }
            return $this->render('uitslagen/showUitslag.html.twig', [
                'order' => $order,
                'turnsters' => $turnsters,
                'userId' => $userId,
            ]);
        }
        $niveaus = $this->getToegestaneNiveaus();
        return $this->render('uitslagen/index.html.twig', array(
            'toegestaneNiveaus' => $niveaus,
        ));
    }

    /**
     * @Route("/diplomaWedstrijdnummerPdf/", name="diplomaWedstrijdnummerPdf")
     * @Method("GET")
     */
    public function diplomaWedstrijdnummerPdf()
    {
        /** @var Turnster[] $results */
        $results = $this->getDoctrine()->getRepository("AppBundle:Turnster")
            ->findBy([
                'wachtlijst' => 0,
                'afgemeld' => 0,
            ]);
        $turnsters = [];
        foreach ($results as $result) {
            $turnsters[] = [
                'id'  => $result->getId(),
                'categorie' => $result->getCategorie(),
                'niveau' => $result->getNiveau(),
                'naam' => $result->getVoornaam() . ' ' . $result->getAchternaam(),
                'vereniging' => $result->getUser()->getVereniging()->getNaam() . ' ' .$result->getUser()
                        ->getVereniging()->getPlaats(),
                'wedstrijdnummer' => $result->getScores()->getWedstrijdnummer(),
            ];
        }
        usort($turnsters, function ($a, $b) {
            return ($a['wedstrijdnummer'] < $b['wedstrijdnummer']) ? -1 : 1;
        });
        $pdf = new DiplomaPdfController('L', 'mm', 'A5');
        $pdf->SetMargins(0,0);
        $pdf->AddFont('Gotham','','Gotham-Light.php');
        $pdf->AddFont('Franklin','','Frabk.php');

        foreach ($turnsters as $turnster) {
            $pdf->AddPage();
            $pdf->Wedstrijdnummer($turnster);
            $pdf->AddPage();
            $pdf->SetFont('Gotham','',18);
            $pdf->HeaderDiploma();
            $pdf->FooterDiploma(self::DATUM_HBC);
            $pdf->ContentDiploma($turnster);
        }
        return new Response($pdf->Output(), 200, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}