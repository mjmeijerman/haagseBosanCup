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
                        'sponsors' =>$this->sponsors,
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
                    if ($result) $content = $result->getContent();
                    return $this->render('default/index.html.twig', array(
                        'content' => $content,
                        'menuItems' => $this->menuItems,
                        'sponsors' =>$this->sponsors,
                    ));
            }
        } else {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
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
        switch ($roles[0])
        {
            case 'ROLE_ADMIN':
                return $this->redirectToRoute('getAdminIndexPage');
                break;
            case 'ROLE_CONTACT':
                return $this->redirectToRoute('getContactpersoonIndexPage');
                break;
            case 'ROLE_ORGANISATIE':
                return $this->redirectToRoute('organisatieGetContent', array('page' => 'Home'));
                break;
            default:
                return $this->redirectToRoute('login_route');
        }
    }

    private function getNieuwsIndexPage()
    {
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Nieuwsbericht')
            ->findBy(
                array(),
                array('id' => 'DESC'),
                10
            );
        $nieuwsItems = array();
        foreach ($results as $result) {
            $nieuwsItems[] = $result->getAll();
        }
        return $this->render('default/nieuws.html.twig', array(
            'nieuwsItems' => $nieuwsItems,
            'menuItems' => $this->menuItems,
            'sponsors' =>$this->sponsors,
        ));
    }

    /**
     * @Route("/inloggen/new_pass/", name="getNewPassPage")
     * @Method({"GET", "POST"})
     */
    public function getNewPassPageAction(Request $request)
    {
        $this->setBasicPageData();
        if ($request->getMethod() == 'POST')
        {
            $username = $this->get('request')->request->get('username');
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:User')
                ->loadUserByUsername($username);
            if (!$user) {
                $this->addFlash(
                    'error',
                    'Deze gebruikersnaam bestaat niet'
                );
            }
            else {
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
            'sponsors' =>$this->sponsors,
        ));
    }
}
