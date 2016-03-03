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


class InschrijvingController extends BaseController
{

    /**
     * @Route("/inschrijven", name="inschrijven")
     * @Method({"GET", "POST"})
     */
    public function inschrijvenPage(Request $request)
    {
        if ($this->inschrijvingToegestaan($request->query->get('token'))) {
            $this->setBasicPageData();
            if ($request->getMethod() == 'POST') {
                $postedToken = $request->request->get('csrfToken');
                if (!empty($postedToken)) {
                    if ($this->isTokenValid($postedToken)) {
                        var_dump($_POST);die();
                        //todo: validatie, return errors + flash messages
                        //todo: safe and return ingevulde data
                        //todo: als alles correct is, reserveer plaatsen, update token, redirect to inschrijven_turnsters
                    }
                }
            }
            $vrijePlekken = $this->getVrijePlekken();
            $verenigingen = $this->getVerenigingen();
            $csrfToken = $this->getToken();
            return $this->render('inschrijven/inschrijven_contactpersoon.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' => $this->sponsors,
                'vrijePlekken' =>$vrijePlekken,
                'verenigingen' => $verenigingen,
                'csrfToken' => $csrfToken,
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
        foreach($results as $result) {
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
