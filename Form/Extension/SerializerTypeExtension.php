<?php
/**
 * SimpleThings FormSerializerBundle
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\FormSerializerBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use SimpleThings\FormSerializerBundle\Form\EventListener\BindRequestListener;

class SerializerTypeExtension extends AbstractTypeExtension
{
    private $encoderRegistry;

    public function __construct($encoderRegistry)
    {
        $this->encoderRegistry = $encoderRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new BindRequestListener($this->encoderRegistry));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'serialize_xml_name'      => 'entry',
            'serialize_xml_value'     => false,
            'serialize_xml_attribute' => false,
            'serialize_xml_inline'    => true,
        ));
    }

    public function getExtendedType()
    {
        return 'form';
    }
}

