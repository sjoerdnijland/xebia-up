<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('guestName', TextType::class, [
                'label' => 'Full name',
                'attr' => ['placeholder' => 'Your name', 'autocomplete' => 'name'],
                'constraints' => [new NotBlank(message: 'Please enter your name')],
            ])
            ->add('guestEmail', EmailType::class, [
                'label' => 'Email address',
                'attr' => ['placeholder' => 'you@company.com', 'autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email'),
                    new Email(message: 'Please enter a valid email address'),
                ],
            ])
            ->add('guestPhone', TelType::class, [
                'label' => 'Phone number',
                'attr' => ['placeholder' => '+31 6 00 00 00 00', 'autocomplete' => 'tel'],
                'constraints' => [new NotBlank(message: 'Please enter your phone number')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
