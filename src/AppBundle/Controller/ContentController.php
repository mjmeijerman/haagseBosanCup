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
        // todo: change this redirect to redirect to nieuwspage
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
                    return $this->redirectToRoute('getInloggenPage');
                    break;
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
     * @Route("/inloggen/", name="getInloggenPage")
     *
     * @Security("has_role('ROLE_INGELOGD')")
     *
     * @Method("GET")
     */
    public function getInloggenPageAction()
    {
        $this->setBasicPageData();
        $user = $this->getUser();
        $roles = $user->getRoles();
        switch ($roles[0])
        {
            case 'ROLE_ADMIN':
                return $this->redirectToRoute('getAdminIndexPage');
                break;
            case 'ROLE_CONTACT':
                return $this->redirectToRoute('getContactpersoonIndexPage');
                break;
            default:
                return $this->render('error/pageNotFound.html.twig', array(
                    'menuItems' => $this->menuItems,
                ));
        }
    }

    /**
     * @Route("/inloggen/new_pass/", name="getNewPassPage")
     * @Method({"GET", "POST"})
     */
    public function getNewPassPageAction(Request $request)
    {
        $error = "";
        $this->setBasicPageData();

        if($request->getMethod() == 'POST')
        {
            $username = $this->get('request')->request->get('username');
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT user
                    FROM AppBundle:User user
                    WHERE user.username = :username')
                ->setParameter('username', $username);
            /** @var User $user */
            $user = $query->setMaxResults(1)->getOneOrNullResult();
            if (count($user) == 0) {
                $error = 'Deze gebruikersnaam bestaat niet';
            }
            else {
                $password = $this->generatePassword();
                $encoder = $this->container
                    ->get('security.encoder_factory')
                    ->getEncoder($user);
                $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
                $em->flush();
                $message = \Swift_Message::newInstance()
                    ->setSubject('Inloggegevens website Haagse Bosan Cup')
                    ->setFrom('info@haagsebosancup.nl')
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            'mails/new_password.txt.twig',
                            array(
                                'username' => $user->getUsername(),
                                'password' => $password
                            )
                        ),
                        'text/plain'
                    );
                try{$this->get('mailer')->send($message);}
                catch(\Exception $e){
                    var_dump($e->getMessage());die;
                }

                $error = 'Een nieuw wachtwoord is gemaild';
            }
        }

        return $this->render('security/newPass.html.twig', array(
            'error' => $error,
            'menuItems' => $this->menuItems,
        ));
    }
}
