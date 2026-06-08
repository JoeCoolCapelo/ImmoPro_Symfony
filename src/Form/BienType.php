<?php

namespace App\Form;

use App\Entity\Bien;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

// Laravel: app/Http/Requests/StoreBienRequest.php

class BienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Maison' => 'maison',
                    'Appartement' => 'appartement',
                    'Terrain' => 'terrain',
                    'Local commercial' => 'local',
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('nature', ChoiceType::class, [
                'choices' => [
                    'Vente' => 'vente',
                    'Location' => 'location',
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('surface', NumberType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'GNF', // Ou dynamique selon la config
                'constraints' => [new NotBlank()],
            ])
            ->add('nbPieces', IntegerType::class, [
                'required' => false,
            ])
            ->add('adresse', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('ville', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('latitude', NumberType::class, [
                'required' => false,
                'scale' => 7,
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 7,
            ])
            ->add('images', FileType::class, [
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All([
                        new Image([
                            'maxSize' => '5M'
                        ])
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bien::class,
        ]);
    }
}
