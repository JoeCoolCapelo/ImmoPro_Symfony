<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Visite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bien', EntityType::class, [
                'class' => Bien::class,
                'choice_label' => 'titre',
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner un bien immobilier.'])],
            ])
            ->add('client', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner un client.'])],
            ])
            ->add('visite', EntityType::class, [
                'class' => Visite::class,
                'choice_label' => 'id',
                'required' => false,
            ])
            ->add('montant', MoneyType::class, [
                'currency' => 'GNF',
                'constraints' => [new NotBlank(['message' => 'Veuillez saisir le montant de la transaction.'])],
            ])
            ->add('commissionPourcentage', NumberType::class, [
                'data' => 10,
                'constraints' => [new NotBlank(['message' => 'Veuillez saisir le pourcentage de commission.'])],
            ])
            ->add('dateTransaction', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [new NotBlank(['message' => 'Veuillez saisir la date de l\'acte.'])],
            ])
            ->add('commentaire', TextareaType::class, [
                'required' => false,
            ])
            ->add('documents', FileType::class, [
                'multiple' => true,
                'mapped' => false,
                'required' => false,
            ])
            ->add('typeLocationPaiement', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Payer par mois (Mensuel - 1 mois d\'avance)' => 'mensuel',
                    'Payer toute l\'année d\'avance (Annuel - 12 mois)' => 'annuel',
                ],
                'data' => 'mensuel',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('chequeAgent', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Chèque encaissé par l\'agent commercial' => 'oui',
                ],
                'data' => 'oui',
                'expanded' => false,
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
