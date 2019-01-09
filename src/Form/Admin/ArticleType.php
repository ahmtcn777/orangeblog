<?php

namespace App\Form\Admin;

use App\Entity\Admin\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('detail')
            ->add('description')
            ->add('keywords')
            ->add('status')
            ->add('userid')
            ->add('categoryid')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'csrf_protection'=>false,
        ]);
    }
}
