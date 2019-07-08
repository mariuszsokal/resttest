<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    private $translator;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, AuthenticationUtils $utils)
    {
        return $this->login($request, $utils);
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request, AuthenticationUtils $utils)
    {
        $error = $utils->getLastAuthenticationError();
        $user = $this->getUser();

        return $this->render('Security/Login.html.twig', [
            'error' => $error,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, AuthenticationUtils $utils, UserPasswordEncoderInterface $encoder)
    {
        if($this->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('app.user.register_success', ['%username%' => $user->getUsername()]));
            return $this->redirectToRoute('login');
        }

        return $this->render('Security/Register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout() { }

    /**
     * @Route("/api/user", methods={"GET","HEAD"})
     */
    public function apiUserGet(Request $request) {
        //curl -H "Content-Type: application/json" -X GET "localhost:8000/api/user?token=J6fMuXWSm0LHij4vVtWl"
        $data = json_decode($request->getContent(), true);
        $token = $request->headers->get('token');

        if(!$this->em->getRepository(User::class)->findOneBy(['Token' => $token])) {
            return $this->json(['error' => 'invalid token']);  
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $data['id']]);
        if(!$user) {
            return $this->json(['error' => 'invalid user']);
        }
        return $this->json(['id' => $user->getId(), 'username' => $user->getUsername(), 'password' => $user->getPassword(), 'token' => $user->getToken()]);
    }

    /**
     * @Route("/api/user", methods={"POST","HEAD"})
     */
    public function apiUserPost(Request $request, UserPasswordEncoderInterface $encoder) {
        $data = json_decode($request->getContent(), true);
        $token = $request->headers->get('token');

        if(!$this->em->getRepository(User::class)->findOneBy(['Token' => $token])) {
            return $this->json(['error' => 'invalid token']);  
        }

        $form = $this->createForm(UserType::class, null, ['csrf_protection' => false]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if($form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();
            return $this->json(['created id' => $user->getId()]);
        }
        else {
            $parsed = [];
            $errors = $form->getErrors(true, false);
            foreach($errors as $error) {
                $parsed[$error->getCause()->getPropertyPath()] = $error->getMessage();
            }
            return $this->json($parsed);
        }
    }

    /**
     * @Route("/api/user", methods={"DELETE","HEAD"})
     */
    public function apiUserDelete(Request $request) {
        $data = json_decode($request->getContent(), true);
        $token = $request->headers->get('token');

        if(!$this->em->getRepository(User::class)->findOneBy(['Token' => $token])) {
            return $this->json(['error' => 'invalid token']);  
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $data['id']]);
        if(!$user) {
            return $this->json(['error' => 'invalid user']);
        }

        $this->em->remove($user);
        $this->em->flush();
        return $this->json(['deleted id' => $data['id']]);
    }

    /**
     * @Route("/api/user", methods={"PUT","HEAD"})
     */
    public function apiUserPut(Request $request, UserPasswordEncoderInterface $encoder) {
        $data = json_decode($request->getContent(), true);
        $token = $request->headers->get('token');

        if(!$this->em->getRepository(User::class)->findOneBy(['Token' => $token])) {
            return $this->json(['error' => 'invalid token']);  
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $data['id']]);
        if(!$user) {
            return $this->json(['error' => 'invalid user']);
        }

        $user->setUsername($data['username']);
        $user->setPassword($encoder->encodePassword($user, $data['password']));
        $this->em->flush();
        return $this->json(['edited user' => $data['id']]);    
    }

    static function generateToken() {
        $token = "";
        $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	    $max = count($characters) - 1;
    	for ($i = 0; $i < 20; $i++) {
	    	$rand = mt_rand(0, $max);
    	    $token .= $characters[$rand];
        }
        return $token;
    }
}
