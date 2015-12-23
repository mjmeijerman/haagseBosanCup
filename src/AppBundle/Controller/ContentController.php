<?php

namespace AppBundle\Controller;

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

    protected $session;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @Route("/", name="getIndexPage")
     * @Method("GET")
     */
    public function indexAction()
    {
        // todo: change this redirect to redirect to nieuwspage
        return $this->redirectToRoute('getContent', array('page' => 'informatie'));
    }

    /**
     * @Route("/pagina/{page}", name="getContent")
     * @Method("GET")
     */
    public function getContentAction($page)
    {
        $this->setBasicPageData();
        if (in_array($page, array('informatie', 'locatie', 'sponsors', 'reglementen', 'contact', 'zaterdag', 'zondag', 'fotos'))) {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT content
                FROM AppBundle:Content content
                WHERE content.pagina = :page
                ORDER BY content.gewijzigd DESC')
            ->setParameter('page', $page);
            $result = $query->setMaxResults(1)->getOneOrNullResult();
            $content = "";
            if (count($result) == 1) $content = $result->getContent();
            return $this->render('default/index.html.twig', array('content' => $content));
        } else {
            $this->session->getFlashBag()->add('Error', 'De pagina kan niet gevonden worden');
            return $this->render('error/pageNotFound.html.twig');
        }
    }

    /**
     * @Route("/pagina/{page}", name="setContent")
     * @Method("POST")
     */
    public function updateContentAction($page, Request $request)
    {
        if($this->session->get("inlog") === "admin") {
            if(in_array($page,array('informatie', 'locatie', 'sponsors', 'reglementen', 'contact', 'zaterdag', 'zondag', 'fotos'))) {
                $content = new Content();
                $content->setGewijzigd(new \DateTime("now"));
                $content->setPagina($page);
                $content->setContent($request->request->get('content'));
                $em = $this->getDoctrine()->getManager();
                $em->persist($content);
                $em->flush();
                return $this->render('default/index.html.twig');
            } else {
                $this->session->getFlashBag()->add('Error', 'De pagina kan niet gevonden worden');
                return $this->render('error/pageNotFound.html.twig');
            }
        } else {
            $this->session->getFlashBag()->add('Error', 'Niet ingelogd als admin');
            return $this->render('error/notAuthorized.html.twig');
        }
    }
}
