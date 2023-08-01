<?php

namespace XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class ServiceWorker extends AbstractController
{
	/**
	 * @return \XF\Mvc\Reply\AbstractReply
	 */
	public function actionCache()
	{
		$viewParams = [
			'files' => $this->getCacheFiles()
		];
		$view = $this->view(
			'XF:ServiceWorker\Cache',
			'',
			$viewParams
		);
		$view->setViewOption('skipDefaultJsonParams', true);
		return $view;
	}

	/**
	 * @return string[]
	 */
	protected function getCacheFiles()
	{
		return [];
	}

	/**
	 * @return \XF\Mvc\Reply\AbstractReply
	 */
	public function actionOffline()
	{
		$viewParams = [
			'cssTemplates' => $this->getOfflineCssTemplates()
		];
		return $this->view(
			'XF:ServiceWorker\Offline',
			'service_worker_offline',
			$viewParams
		);
	}

	public function getOfflineCssTemplates()
	{
		return ['public:offline.less'];
	}

	/**
	 * @param string $action
	 */
	public function checkCsrfIfNeeded($action, ParameterBag $params)
	{
		if (strtolower($action) == 'cache')
		{
			return;
		}

		parent::checkCsrfIfNeeded($action, $params);
	}
}