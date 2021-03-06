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

namespace SimpleThings\FormSerializerBundle\Form\EventListener;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

use SimpleThings\FormSerializerBundle\Serializer\EncoderRegistry;
use SimpleThings\FormSerializerBundle\Serializer\NamingStrategy\CamelCaseStrategy;
use SimpleThings\FormSerializerBundle\Serializer\NamingStrategy\NamingStrategy;

class BindRequestListener implements EventSubscriberInterface
{
    private $decoder;
    private $namingStrategy;

    public function __construct(DecoderInterface $decoder, NamingStrategy $namingStrategy = null)
    {
        $this->decoder        = $decoder;
        $this->namingStrategy = $namingStrategy ?: new CamelCaseStrategy();
    }

    public static function getSubscribedEvents()
    {
        // High priority in order to supersede other listeners
        return array(FormEvents::PRE_BIND => array('preBind', 129));
    }

    public function preBind(FormEvent $event)
    {
        $form    = $event->getForm();
        $request = $event->getData();

        if ( ! $request instanceof Request) {
            return;
        }

        $format = $request->getContentType();

        if ( ! $this->decoder->supportsDecoding($format)) {
            return;
        }

        $content = $request->getContent();
        $data    = $this->decoder->decode($content, $format);

        $event->setData($this->unserializeForm($data, $form));
    }

    private function unserializeForm($data, $form)
    {
        if ($form->hasAttribute('serialize_collection_form')) {
            $form   = $form->getAttribute('serialize_collection_form');
            $result = array();

            foreach ($data as $key => $child) {
                $result[$key] = $this->unserializeForm($child, $form);
            }

            return $result;
        } else if ( ! $form->hasChildren()) {
            return $data;
        }

        $result = array();

        foreach ($form->getChildren() as $child) {
            $options     = $child->getConfig()->getOptions();

            if (isset($options['read_only']) && $options['read_only']) {
                continue;
            }

            $name        = $this->namingStrategy->translateName($child);
            $isAttribute = isset($options['serialize_xml_attribute']) && $options['serialize_xml_attribute'];

            if ($options['serialize_xml_value'] && isset($data['#'])) {
                $value = $data['#'];
            } else if (! $options['serialize_xml_inline']) {
                $value = isset($data[$name][$options['serialize_xml_name']])
                    ? $data[$name][$options['serialize_xml_name']]
                    : null;
            } else {
                $value = isset($data['@' . $name])
                    ? $data['@' . $name]
                    : (isset($data[$name]) ? $data[$name] : null);
            }

            $result[$child->getName()] = $this->unserializeForm($value, $child);
        }

        return $result;
    }
}

