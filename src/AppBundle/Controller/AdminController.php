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
use AppBundle\Form\Type\ContentType;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminController extends BaseController
{
    /**
     * @Route("/admin/", name="getAdminIndexPage")
     * @Method("GET")
     */
    public function getIndexPageAction()
    {
        $this->setBasicPageData();
        return $this->render('inloggen/adminIndex.html.twig', array(
            'menuItems' => $this->menuItems,
        ));
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

    /**
     * @Route("/donar/{page}/edit/", defaults={"page" = "geschiedenis"}, name="editDefaultPage")
     * @Method({"GET", "POST"})
     */
    public function editDefaultPageAction($page, Request $request)
    {
        $this->setBasicPageData();
        if ($this->checkIfPageExists($page)) {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT content
            FROM AppBundle:Content content
            WHERE content.pagina = :page
            ORDER BY content.gewijzigd DESC')
                ->setParameter('page', $page);
            /** @var Content $content */
            $content = $query->setMaxResults(1)->getOneOrNullResult();
            if(count($content) > 0)
            {
                $form = $this->createForm(new ContentType(), $content);
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $editedContent = new Content();
                    $editedContent->setGewijzigd(new \DateTime('NOW'));
                    $editedContent->setPagina($page);
                    $editedContent->setContent($content->getContent());
                    $em->detach($content);
                    $em->persist($editedContent);
                    $em->flush();
                    return $this->redirectToRoute('getContent', array('page' => $page));
                }
                else {
                    return $this->render('default/editIndex.html.twig', array(
                        'content' => $content->getContent(),
                        'menuItems' => $this->menuItems,
                        'form' => $form->createView(),
                    ));
                }
            }
            else
            {
                return $this->render('error/pageNotFound.html.twig', array(
                    'menuItems' => $this->menuItems,
                ));
            }

        }
        else
        {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
            ));
        }
    }


}
