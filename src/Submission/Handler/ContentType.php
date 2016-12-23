<?php

namespace Bolt\Extension\Bolt\BoltForms\Submission\Handler;

use Bolt\Exception\StorageException;
use Bolt\Extension\Bolt\BoltForms\Config\FieldMap\ContentType as FieldMap;
use Bolt\Extension\Bolt\BoltForms\Config\MetaData;
use Bolt\Extension\Bolt\BoltForms\Exception\InternalProcessorException;
use Bolt\Extension\Bolt\BoltForms\FormData;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository\ContentRepository;
use Carbon\Carbon;

/**
 * ContentType storage.
 *
 * Copyright (c) 2014-2016 Gawain Lynch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License or GNU Lesser
 * General Public License as published by the Free Software Foundation,
 * either version 3 of the Licenses, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @copyright Copyright (c) 2014-2016, Gawain Lynch
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 * @license   http://opensource.org/licenses/LGPL-3.0 GNU Lesser General Public License 3.0
 */
class ContentType extends AbstractHandler
{
    /**
     * Write out form data to a specified ContentType record.
     *
     * @param string   $contentType
     * @param FormData $formData
     * @param MetaData $formMetaData
     * @param FieldMap $fieldMap
     *
     * @throws InternalProcessorException
     */
    public function handle($contentType, FormData $formData, MetaData $formMetaData, FieldMap $fieldMap)
    {
        try {
            /** @var ContentRepository $repo */
            $repo = $this->getEntityManager()->getRepository($contentType);
        } catch (StorageException $e) {
            throw new InternalProcessorException(sprintf('Invalid ContentType name `%s` specified.', $contentType), $e->getCode(), $e, false);
        }

        // Get an empty record for our ContentType
        $record = $this->getRecord($repo, $formData);

        // Set a published date
        $record->setStatus('published');
        if (!$formData->has('datepublish')) {
            $record->setDatepublish(Carbon::now());
        }

        foreach ($formData->all() as $name => $data) {
            // Store the data array into the record
            if ($fieldMap->has($name) === false) {
                continue;
            }

            $fieldName = $fieldMap->get($name);
            $record->set($fieldName, $data);
        }

        // Add any meta values that are requested for 'database' use
        foreach ($formMetaData->getUsedMeta('database') as $key => $value) {
            $record->set($key, $value);
        }

        try {
            $repo->save($record);
        } catch (\Exception $e) {
            $message = sprintf('An exception occurred saving submission to ContentType table `%s`', $contentType);
            throw new InternalProcessorException($message, $e->getCode(), $e, false);
        }
    }


    /**
     * Get an appropriate entity object.
     *
     * @param ContentRepository $repo
     * @param FormData          $formData
     *
     * @return Entity\Content
     */
    protected function getRecord(ContentRepository $repo, FormData $formData)
    {
        if ($formData->has('id') === false) {
            return $repo->getEntityBuilder()->getEntity();
        }

        $record = $repo->find($formData->get('id'));
        if ($record === false) {
            return $repo->getEntityBuilder()->getEntity();
        }

        return $record;
    }
}
