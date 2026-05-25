<?php

namespace App\Form;

use App\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('shortName', TextType::class, ['label' => 'Nome curto', 'required' => false])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('city', TextType::class, ['label' => 'Cidade', 'required' => false])
            ->add('foundedAt', DateType::class, ['label' => 'Fundação', 'required' => false, 'widget' => 'single_text'])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativo' => 'ATIVO', 'Inativo' => 'INATIVO'],
            ])
            ->add('logo', TextType::class, [
                'label' => 'Logo (URL)',
                'required' => false,
                'help' => 'Preenchido automaticamente se você enviar um arquivo abaixo. Também pode colar aqui a URL de uma imagem já hospedada em outro lugar.',
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Ou enviar um arquivo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
                        mimeTypesMessage: 'Envie uma imagem PNG, JPEG, WEBP ou SVG de até 2MB.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Team::class]);
    }
}
