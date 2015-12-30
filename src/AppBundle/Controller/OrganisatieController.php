<?php

namespace AppBundle\Controller;

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

/**
 * @Security("has_role('ROLE_ORGANISATIE')")
 */
class OrganisatieController extends BaseController
{
    /**
     * @Route("/organisatie/", name="getOrganisatieIndexPage")
     * @Method("GET")
     */
    public function getIndexPageAction()
    {
        $this->setBasicPageData();
        return $this->render('organisatie/organisatieIndex.html.twig', array(
            'menuItems' => $this->menuItems,
        ));
    }

}