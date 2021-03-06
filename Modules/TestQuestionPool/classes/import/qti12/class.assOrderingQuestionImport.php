<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

include_once "./Modules/TestQuestionPool/classes/import/qti12/class.assQuestionImport.php";

/**
* Class for ordering question imports
*
* assOrderingQuestionImport is a class for ordering question imports
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assOrderingQuestionImport extends assQuestionImport
{
	/**
	* Creates a question from a QTI file
	*
	* Receives parameters from a QTI parser and creates a valid ILIAS question object
	*
	* @param object $item The QTI item object
	* @param integer $questionpool_id The id of the parent questionpool
	* @param integer $tst_id The id of the parent test if the question is part of a test
	* @param object $tst_object A reference to the parent test object
	* @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	* @param array $import_mapping An array containing references to included ILIAS objects
	* @access public
	*/
	function fromXML(&$item, $questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		global $ilUser;

		// empty session variable for imported xhtml mobs
		unset($_SESSION["import_mob_xhtml"]);
		$presentation = $item->getPresentation(); 
		$duration = $item->getDuration();
		$shuffle = 0;
		$now = getdate();
		$foundimage = FALSE;
		$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
		$answers = array();
		foreach ($presentation->order as $entry)
		{
			switch ($entry["type"])
			{
				case "response":
					$response = $presentation->response[$entry["index"]];
					$rendertype = $response->getRenderType(); 
					switch (strtolower(get_class($rendertype)))
					{
						case "ilqtirenderchoice":
							$shuffle = $rendertype->getShuffle();
							$answerorder = 0;
							foreach ($rendertype->response_labels as $response_label)
							{
								$ident = $response_label->getIdent();
								$answertext = "";
								foreach ($response_label->material as $mat)
								{
									for ($m = 0; $m < $mat->getMaterialCount(); $m++)
									{
										$foundmat = $mat->getMaterial($m);
										if (strcmp($foundmat["type"], "mattext") == 0)
										{
											$answertext .= $foundmat["material"]->getContent();
										}
										if (strcmp($foundmat["type"], "matimage") == 0)
										{
											$foundimage = TRUE;
											$answerimage = array(
												"imagetype" => $foundmat["material"]->getImageType(),
												"label" => $foundmat["material"]->getLabel(),
												"content" => $foundmat["material"]->getContent()
											);
										}
									}
								}
								$answers[$ident] = array(
									"answertext" => $answertext,
									"answerimage" => $answerimage,
									"points" => 0,
									"answerorder" => $answerorder++,
									"correctness" => "",
									"action" => ""
								);
							}
							break;
					}
					break;
			}
		}
		$responses = array();
		$feedbacksgeneric = array();
		foreach ($item->resprocessing as $resprocessing)
		{
			foreach ($resprocessing->respcondition as $respcondition)
			{
				$ident = "";
				$correctness = 1;
				$conditionvar = $respcondition->getConditionvar();
				foreach ($conditionvar->order as $order)
				{
					switch ($order["field"])
					{
						case "arr_not":
							$correctness = 0;
							break;
						case "varequal":
							$ident = $conditionvar->varequal[$order["index"]]->getContent();
							$orderindex = $conditionvar->varequal[$order["index"]]->getIndex();
							break;
					}
				}
				foreach ($respcondition->setvar as $setvar)
				{
					if (strcmp($ident, "") != 0)
					{
						$answers[$ident]["solutionorder"] = $orderindex;
						$answers[$ident]["action"] = $setvar->getAction();
						$answers[$ident]["points"] = $setvar->getContent();
					}
				}
				if (count($respcondition->displayfeedback))
				{
					foreach ($respcondition->displayfeedback as $feedbackpointer)
					{
						if (strlen($feedbackpointer->getLinkrefid()))
						{
							foreach ($item->itemfeedback as $ifb)
							{
								if (strcmp($ifb->getIdent(), "response_allcorrect") == 0)
								{
									// found a feedback for the identifier
									if (count($ifb->material))
									{
										foreach ($ifb->material as $material)
										{
											$feedbacksgeneric[1] = $material;
										}
									}
									if ((count($ifb->flow_mat) > 0))
									{
										foreach ($ifb->flow_mat as $fmat)
										{
											if (count($fmat->material))
											{
												foreach ($fmat->material as $material)
												{
													$feedbacksgeneric[1] = $material;
												}
											}
										}
									}
								} 
								else if (strcmp($ifb->getIdent(), "response_onenotcorrect") == 0)
								{
									// found a feedback for the identifier
									if (count($ifb->material))
									{
										foreach ($ifb->material as $material)
										{
											$feedbacksgeneric[0] = $material;
										}
									}
									if ((count($ifb->flow_mat) > 0))
									{
										foreach ($ifb->flow_mat as $fmat)
										{
											if (count($fmat->material))
											{
												foreach ($fmat->material as $material)
												{
													$feedbacksgeneric[0] = $material;
												}
											}
										}
									} 
								}
							}
						}
					}
				}
			}
		}
		$type = 1; // terms
		if ($foundimage)
		{
			$type = 0; // pictures
		}
		$this->object->setTitle($item->getTitle());
		$this->object->setNrOfTries($item->getMaxattempts());
		$this->object->setComment($item->getComment());
		$this->object->setAuthor($item->getAuthor());
		$this->object->setOwner($ilUser->getId());
		$this->object->setQuestion($this->object->QTIMaterialToString($item->getQuestiontext()));
		$this->object->setOrderingType($type);
		$this->object->setObjId($questionpool_id);
		$this->object->setThumbGeometry($item->getMetadataEntry("thumb_geometry"));
		$this->object->setElementHeight($item->getMetadataEntry("element_height"));
		$this->object->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
		$this->object->setShuffle($shuffle);
		$points = 0;
		$solanswers = array();
		foreach ($answers as $answer)
		{
			$solanswers[$answer["solutionorder"]] = $answer;
		}
		ksort($solanswers);
		foreach ($solanswers as $answer)
		{
			$points += $answer["points"];
			if ($type == 1)
			{
				$this->object->addAnswer($answer["answertext"]);
			}
			else
			{
				$this->object->addAnswer($answer["answerimage"]["label"]);
			}
		}
		$points = ($item->getMetadataEntry("points") > 0) ? $item->getMetadataEntry("points") : $points;
		$this->object->setPoints($points);
		$this->object->saveToDb();
		if (count($item->suggested_solutions))
		{
			foreach ($item->suggested_solutions as $suggested_solution)
			{
				$this->object->setSuggestedSolution($suggested_solution["solution"]->getContent(), $suggested_solution["gap_index"], true);
			}
			$this->object->saveToDb();
		}
		foreach ($answers as $answer)
		{
			if ($type == 0)
			{
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$image =& base64_decode($answer["answerimage"]["content"]);
				$imagepath = $this->object->getImagePath();
				if (!file_exists($imagepath))
				{
					ilUtil::makeDirParents($imagepath);
				}
				$imagepath .=  $answer["answerimage"]["label"];
				$fh = fopen($imagepath, "wb");
				if ($fh == false)
				{
//									global $ilErr;
//									$ilErr->raiseError($this->object->lng->txt("error_save_image_file") . ": $php_errormsg", $ilErr->MESSAGE);
//									return;
				}
				else
				{
					$imagefile = fwrite($fh, $image);
					fclose($fh);
				}
				// create thumbnail file
				$thumbpath = $imagepath . "." . "thumb.jpg";
				ilUtil::convertImage($imagepath, $thumbpath, "JPEG", $this->object->getThumbGeometry());
			}
		}
		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacksgeneric[$correctness] = $m;
		}
		$questiontext = $this->object->getQuestion();
		$answers =& $this->object->getAnswers();
		// handle the import of media objects in XHTML code
		if (is_array($_SESSION["import_mob_xhtml"]))
		{
			include_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";
			include_once "./Services/RTE/classes/class.ilRTE.php";
			foreach ($_SESSION["import_mob_xhtml"] as $mob)
			{
				if ($tst_id > 0)
				{
					$importfile = $this->getTstImportArchivDirectory() . '/' . $mob["uri"];
				}
				else
				{
					$importfile = $this->getQplImportArchivDirectory() . '/' . $mob["uri"];
				}
				
				$GLOBALS['ilLog']->write(__METHOD__.': import mob from dir: '. $importfile);
				
				$media_object =& ilObjMediaObject::_saveTempFileAsMediaObject(basename($importfile), $importfile, FALSE);
				ilObjMediaObject::_saveUsage($media_object->getId(), "qpl:html", $this->object->getId());
				$questiontext = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $questiontext);
				foreach ($answers as $key => $value)
				{
					$answer_obj =& $answers[$key];
					$answer_obj->setAnswertext(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $answer_obj->getAnswertext()));
				}
				foreach ($feedbacksgeneric as $correctness => $material)
				{
					$feedbacksgeneric[$correctness] = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $material);
				}
			}
		}
		$this->object->setQuestion(ilRTE::_replaceMediaObjectImageSrc($questiontext, 1));
		foreach ($answers as $key => $value)
		{
			$answer_obj =& $answers[$key];
			$answer_obj->setAnswertext(ilRTE::_replaceMediaObjectImageSrc($answer_obj->getAnswertext(), 1));
		}
		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$this->object->saveFeedbackGeneric($correctness, ilRTE::_replaceMediaObjectImageSrc($material, 1));
		}
		$this->object->saveToDb();
		if ($tst_id > 0)
		{
			$q_1_id = $this->object->getId();
			$question_id = $this->object->duplicate(true, null, null, null, $tst_id);
			$tst_object->questions[$question_counter++] = $question_id;
			$import_mapping[$item->getIdent()] = array("pool" => $q_1_id, "test" => $question_id);
		}
		else
		{
			$import_mapping[$item->getIdent()] = array("pool" => $this->object->getId(), "test" => 0);
		}
	}
}

?>
