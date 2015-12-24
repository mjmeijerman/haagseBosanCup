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

    /**
     * @Route("/", name="getIndexPage")
     * @Method("GET")
     */
    public function indexAction()
    {
        // todo: change this redirect to redirect to nieuwspage
        return $this->redirectToRoute('getContent', array('page' => 'informatie'));
    }

    private function checkIfPageExists($page)
    {
        $pageExists = false;
        foreach ($this->menuItems['hoofdmenuItems'] as $item) {
            if ($pageExists) break;
            if ($item['naam'] == $page) {
                $pageExists = true;
                break;
            }
            if (isset($item['submenuItems'])) {
                foreach ($item['submenuItems'] as $subItem) {
                    if ($subItem['naam'] == $page) {
                        $pageExists = true;
                        break;
                    }
                }
            }
        }
        return $pageExists;
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
                default:
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
                return $this->render('default/index.html.twig', array(
                    'content' => $content,
                    'menuItems' => $this->menuItems,
                ));
            }
        } else {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
            ));
        }
    }

    /**
     * @Route("/pagina/{page}", name="setContent")
     * @Method("POST")
     */
    public function updateContentAction($page, Request $request)
    {
        if ($this->checkIfPageExists($page)) {
            switch ($page) {
                default:
                $content = new Content();
                $content->setGewijzigd(new \DateTime("now"));
                $content->setPagina($page);
                $content->setContent($request->request->get('content'));
                $em = $this->getDoctrine()->getManager();
                $em->persist($content);
                $em->flush();
                return $this->render('default/index.html.twig');
            }
        } else {
            return $this->render('error/pageNotFound.html.twig');
        }
    }
}
