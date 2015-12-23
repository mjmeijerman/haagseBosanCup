<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;


class ContentController extends Controller
{

    protected $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * @Route("/pagina/{page}", name="getContent")
     * @Method("GET")
     */
    public function getPageAction($page)
    {
        if(in_array($page,array('informatie', 'locatie', 'sponsors', 'reglementen', 'contact', 'zaterdag', 'zondag', 'fotos')))
        {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT content
                FROM AppBundle:Content content
                WHERE content.pagina = :page
                ORDER BY content.gewijzigd DESC')
            ->setParameter('page', $page);
            $content = $query->setMaxResults(1)->getOneOrNullResult();
            return $this->render('default/index.html.twig');
        }
        else
        {
            $this->session->getFlashBag()->add('Error', 'De pagina kan niet gevonden worden');
            return $this->render('error/pageNotFound.html.twig');
        }
    }

    /**
     * @Route("/pagina/{page}", name="setContent")
     * @Method("POST")
     */
    public function updatePageAction($page, Request $request)
    {
        if($this->session->get("inlog") === "admin")
        {
            if(in_array($page,array('informatie', 'locatie', 'sponsors', 'reglementen', 'contact', 'zaterdag', 'zondag', 'fotos')))
            {
                $content = new Content();
                $content->setGewijzigd(new \DateTime("now"));
                $content->setPagina($page);
                $content->setContent($request->request->get('content'));
                $em = $this->getDoctrine()->getManager();
                $em->persist($content);
                $em->flush();
                return $this->render('default/index.html.twig');
            }
            else
            {
                $this->session->getFlashBag()->add('Error', 'De pagina kan niet gevonden worden');
                return $this->render('error/pageNotFound.html.twig');
            }
        }
        else
        {
            $this->session->getFlashBag()->add('Error', 'Niet ingelogd als admin');
            return $this->render('error/notAuthorized.html.twig');
        }
    }
}
