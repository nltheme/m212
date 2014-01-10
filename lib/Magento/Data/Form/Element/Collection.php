<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Data
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Form element collection
 *
 * @category    Magento
 * @package     Magento_Data
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Data\Form\Element;

class Collection implements \ArrayAccess, \IteratorAggregate
{

    /**
     * Elements storage
     *
     * @var array
     */
    private $_elements;

    /**
     * Elements container
     *
     * @var \Magento\Data\Form\AbstractForm
     */
    private $_container;

    /**
     * Class constructor
     *
     * @param \Magento\Data\Form\AbstractForm $container
     */
    public function __construct(\Magento\Data\Form\AbstractForm $container)
    {
        $this->_elements = array();
        $this->_container = $container;
    }

    /**
     * Implementation of \IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_elements);
    }

    /**
     * Implementation of \ArrayAccess:offsetSet()
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->_elements[$key] = $value;
    }

    /**
     * Implementation of \ArrayAccess:offsetGet()
     *
     * @param mixed $key
     */
    public function offsetGet($key)
    {
        return $this->_elements[$key];
    }

    /**
     * Implementation of \ArrayAccess:offsetUnset()
     *
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        unset($this->_elements[$key]);
    }

    /**
     * Implementation of \ArrayAccess:offsetExists()
     *
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return isset($this->_elements[$key]);
    }

    /**
     * Add element to collection
     *
     * @todo get it straight with $after
     * @param \Magento\Data\Form\Element\AbstractElement $element
     * @param bool|string $after
     *
     * @return \Magento\Data\Form\Element\Collection
     */
    public function add(\Magento\Data\Form\Element\AbstractElement $element, $after = false)
    {
        // Set the Form for the node
        if ($this->_container->getForm() instanceof \Magento\Data\Form) {
            $element->setContainer($this->_container);
            $element->setForm($this->_container->getForm());
        }

        if ($after === false) {
            $this->_elements[] = $element;
        }
        elseif ($after === '^') {
            array_unshift($this->_elements, $element);
        }
        elseif (is_string($after)) {
            $newOrderElements = array();
            foreach ($this->_elements as $index => $currElement) {
                if ($currElement->getId() == $after) {
                    $newOrderElements[] = $currElement;
                    $newOrderElements[] = $element;
                    $this->_elements = array_merge($newOrderElements, array_slice($this->_elements, $index + 1));
                    return $element;
                }
                $newOrderElements[] = $currElement;
            }
            $this->_elements[] = $element;
        }

        return $element;
    }

    /**
     * Sort elements by values using a user-defined comparison function
     *
     * @param mixed $callback
     * @return \Magento\Data\Form\Element\Collection
     */
    public function usort($callback)
    {
        usort($this->_elements, $callback);
        return $this;
    }

    /**
     * Remove element from collection
     *
     * @param mixed $elementId
     * @return \Magento\Data\Form\Element\Collection
     */
    public function remove($elementId)
    {
        foreach ($this->_elements as $index => $element) {
            if ($elementId == $element->getId()) {
                unset($this->_elements[$index]);
            }
        }
        // Renumber elements for further correct adding and removing other elements
        $this->_elements = array_merge($this->_elements, array());
        return $this;
    }

    /**
     * Count elements in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->_elements);
    }

    /**
     * Find element by ID
     *
     * @param mixed $elementId
     * @return \Magento\Data\Form\Element\AbstractElement|null
     */
    public function searchById($elementId)
    {
        foreach ($this->_elements as $element) {
            if ($element->getId() == $elementId) {
                return $element;
            }
        }
        return null;
    }

}