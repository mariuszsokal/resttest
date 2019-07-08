<?php declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Controller\SecurityController;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserType extends AbstractType
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'app.user.username'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'app.user.password'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'app.submit.register'
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $user = $event->getData();
                if($user->getId() === null) {
                    $user->setToken(SecurityController::generateToken());
                    $user->setPassword($this->encoder->encodePassword($user, $user->getPassword()));
                }
            })
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}