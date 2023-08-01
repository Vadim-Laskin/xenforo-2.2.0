<?php

namespace XF\Option;

class Giphy extends AbstractOption
{
	public static function verifyOption(&$value, \XF\Entity\Option $option)
	{
		if ($option->isInsert())
		{
			return true;
		}

		if (empty($value['enabled']))
		{
			// attempt to preserve existing API key if not currently empty
			if (!empty($option->option_value['api_key']))
			{
				$value['api_key'] = $option->option_value['api_key'];
			}

			if ($option->option_value['enabled'])
			{
				// just disabled
				self::removeGiphyToolbarButton();
			}

			return true;
		}

		if ($value['enabled'] && !$value['api_key'])
		{
			$option->error(\XF::phrase('please_enter_value_for_required_field_x', ['field' => 'giphy[api_key]']));
			return false;
		}

		if ($value['enabled'] && !$option->option_value['enabled'])
		{
			// just enabled
			self::insertGiphyToolbarButton();
		}

		return true;
	}

	public static function insertGiphyToolbarButton()
	{
		self::updateToolbarButtons(
			function(array $buttonSet)
			{
				$insertPosition = null;
				foreach ($buttonSet AS $k => $button)
				{
					if ($button == 'xfSmilie')
					{
						$insertPosition = $k + 1;
					}
					else if ($button == 'xfInsertGif')
					{
						// already have it
						$insertPosition = null;
						break;
					}
				}

				if ($insertPosition !== null)
				{
					array_splice($buttonSet, $insertPosition, 0, ['xfInsertGif']);
				}

				return $buttonSet;
			}
		);
	}

	public static function removeGiphyToolbarButton()
	{
		self::updateToolbarButtons(
			function(array $buttonSet)
			{
				$newButtons = [];

				foreach ($buttonSet AS $button)
				{
					if ($button == 'xfInsertGif')
					{
						continue;
					}

					$newButtons[] = $button;
				}

				return $newButtons;
			}
		);
	}

	protected static function updateToolbarButtons(callable $buttonsCallback)
	{
		$toolbarButtons = \XF::options()->editorToolbarConfig;

		foreach ($toolbarButtons AS $type => &$group)
		{
			if (!is_array($group))
			{
				continue;
			}

			foreach ($group AS &$groupData)
			{
				if (!is_array($groupData) || empty($groupData['buttons']))
				{
					continue;
				}

				$groupData['buttons'] = $buttonsCallback($groupData['buttons']);
			}
		}

		\XF::repository('XF:Option')->updateOption('editorToolbarConfig', $toolbarButtons);
	}
}