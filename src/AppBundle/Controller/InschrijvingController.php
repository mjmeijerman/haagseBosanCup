<?php

namespace AppBundle\Controller;

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


class InschrijvingController extends BaseController
{

    /**
     * @Route("/inschrijven", name="inschrijven")
     * @Method("GET")
     */
    public function inschrijvenPage(Request $request)
    {
        if ($this->inschrijvingToegestaan($request->query->get('token'))) {
            die('Inschrijvingspagina hier');
            // todo: return inschrijvingspagina
        } else {
            return $this->redirectToRoute('getContent', array('page' => 'Inschrijvingsinformatie'));
        }
    }
}
