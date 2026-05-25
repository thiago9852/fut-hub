<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('email', EmailType::class, ['label' => 'E-mail'])
            ->add('role', ChoiceType::class, [
                'label' => 'Perfil',
                'mapped' => false,
                'data' => $options['current_role'],
                'choices' => [
                    'Administrador' => 'ROLE_ADMIN',
                    'Gerente' => 'ROLE_MANAGER',
                    'Editor' => 'ROLE_EDITOR',
                    'Visualizador' => 'ROLE_VIEWER',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativo' => 'ATIVO', 'Inativo' => 'INATIVO'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_new'] ? 'Senha' : 'Nova senha (deixe em branco para manter)',
                'mapped' => false,
                'required' => $options['is_new'],
                'constraints' => $options['is_new'] ? [new Assert\NotBlank(), new Assert\Length(min: 6)] : [],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'is_new' => true, 'current_role' => 'ROLE_VIEWER']);
    }
}
