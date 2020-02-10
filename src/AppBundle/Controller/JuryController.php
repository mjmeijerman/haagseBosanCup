<?php

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Jurylid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class JuryController extends BaseController
{
    /**
     * @Route("/jury/bevestig/{confirmationId}/", name="confirmJudge", methods={"GET", "POST"})
     */
    public function confirmJudge(Request $request, $confirmationId)
    {
        $this->setBasicPageData();

        /** @var Jurylid $juryLid */
        $juryLid = $this->getDoctrine()->getRepository(Jurylid::class)->findOneBy(
            ['confirmationId' => $confirmationId]
        );
        if (!$juryLid) {
            return $this->render(
                'jury/not_found.html.twig',
                array(
                    'menuItems' => $this->menuItems,
                    'sponsors'  => $this->sponsors,
                )
            );
        }

        if ($request->getMethod() === 'POST') {
            $juryLid->setVoornaam($request->request->get('voornaam'));
            $juryLid->setAchternaam($request->request->get('achternaam'));
            $juryLid->setBrevet($request->request->get('brevet'));
            $juryLid->setPhoneNumber($request->request->get('phone'));
            $juryLid->setOpmerking($request->request->get('remarks'));
            $juryLid->setIsConfirmed(true);

            $dag = '';
            switch ($request->request->get('dag')) {
                case 'za':
                    $juryLid->setZaterdag(true);
                    $juryLid->setZondag(false);
                    $dag = 'Zaterdag';
                    break;
                case 'zo':
                    $juryLid->setZaterdag(false);
                    $juryLid->setZondag(true);
                    $dag = 'Zondag';
                    break;
                case 'zazo':
                    $juryLid->setZaterdag(true);
                    $juryLid->setZondag(true);
                    $dag = 'Zaterdag en Zondag';
                    break;
            }

            $this->addToDB($juryLid);

            $this->sendEmail(
                'Bevestiging ontvangen',
                $juryLid->getEmail(),
                'mails/confirmation_received.txt.twig',
                [
                    'voornaam'   => $juryLid->getVoornaam(),
                    'achternaam' => $juryLid->getAchternaam(),
                    'email'      => $juryLid->getEmail(),
                    'phone'      => $juryLid->getPhoneNumber(),
                    'brevet'     => $juryLid->getBrevet(),
                    'dag'        => $dag,
                    'opmerking'  => $juryLid->getOpmerking(),
                ]
            );

            return $this->redirectToRoute('getIndexPage');
        }

        if ($juryLid->getIsConfirmed()) {
            return $this->render(
                'jury/allready_confirmed.html.twig',
                array(
                    'menuItems' => $this->menuItems,
                    'sponsors'  => $this->sponsors,
                )
            );
        }

        return $this->render(
            'jury/confirm.html.twig',
            array(
                'menuItems'      => $this->menuItems,
                'sponsors'       => $this->sponsors,
                'jurylid'        => $juryLid,
                'confirmationId' => $confirmationId,
            )
        );
    }

    /**
     * @Route("/jury/uitschrijven/{confirmationId}/", name="unsubscribeJudge", methods={"POST"})
     */
    public function unsubscribeJudge($confirmationId)
    {
        $this->setBasicPageData();

        /** @var Jurylid $juryLid */
        $juryLid = $this->getDoctrine()->getRepository(Jurylid::class)->findOneBy(
            ['confirmationId' => $confirmationId]
        );
        if (!$juryLid) {
            return $this->render(
                'jury/not_found.html.twig',
                array(
                    'menuItems' => $this->menuItems,
                    'sponsors'  => $this->sponsors,
                )
            );
        }

        if ($juryLid->getIsConfirmed()) {
            return $this->render(
                'jury/allready_confirmed.html.twig',
                array(
                    'menuItems' => $this->menuItems,
                    'sponsors'  => $this->sponsors,
                )
            );
        }

        $contactpersoon = $juryLid->getUser();

        $this->sendEmail(
            'Uitschrijving jurylid',
            $juryLid->getUser()->getEmail(),
            'mails/unsubscribe_judge_to_club.txt.twig',
            [
                'contactpersoon_voornaam'   => $contactpersoon->getVoornaam(),
                'contactpersoon_achternaam' => $contactpersoon->getAchternaam(),
                'jurylid_voornaam'          => $juryLid->getVoornaam(),
                'jurylid_achternaam'        => $juryLid->getAchternaam(),
                'jurylid_email'             => $juryLid->getEmail(),
            ]
        );

        $this->sendEmail(
            'Uitschrijving jurylid',
            $juryLid->getEmail(),
            'mails/unsubscribe_judge_to_judge.txt.twig',
            [
                'jurylid_voornaam'   => $juryLid->getVoornaam(),
                'jurylid_achternaam' => $juryLid->getAchternaam(),
                'vereniging'         => $contactpersoon->getVereniging()->getNaam(
                    ) . ' ' . $contactpersoon->getVereniging()->getPlaats()
            ]
        );

        $this->sendEmail(
            'Uitschrijving jurylid',
            self::TOURNAMENT_CONTACT_EMAIL,
            'mails/unsubscribe_judge_to_organisation.txt.twig',
            [
                'contactpersoon_voornaam'   => $contactpersoon->getVoornaam(),
                'contactpersoon_achternaam' => $contactpersoon->getAchternaam(),
                'jurylid_voornaam'          => $juryLid->getVoornaam(),
                'jurylid_achternaam'        => $juryLid->getAchternaam(),
                'jurylid_email'             => $juryLid->getEmail(),
                'vereniging'                => $contactpersoon->getVereniging()->getNaam(
                    ) . ' ' . $contactpersoon->getVereniging()->getPlaats()
            ]
        );

        $this->removeFromDB($juryLid);

        return $this->redirectToRoute('getIndexPage');
    }
}
