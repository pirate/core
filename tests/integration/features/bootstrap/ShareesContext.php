<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../../../../lib/composer/autoload.php';

/**
 * Features context.
 */
class ShareesContext implements Context, SnippetAcceptingContext {
	use Provisioning;
	use AppConfiguration;

	/**
	 * @When /^getting sharees for$/
	 * @param \Behat\Gherkin\Node\TableNode $body
	 */
	public function whenGettingShareesFor($body) {
		$url = '/apps/files_sharing/api/v1/sharees';
		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$parameters = [];
			foreach ($body->getRowsHash() as $key => $value) {
				$parameters[] = $key . '=' . $value;
			}
			if (!empty($parameters)) {
				$url .= '?' . implode('&', $parameters);
			}
		}

		$this->sendingTo('GET', $url);
	}

	/**
	 * @Then /^"([^"]*)" sharees returned (are|is empty)$/
	 * @param string $shareeType
	 * @param string $isEmpty
	 * @param \Behat\Gherkin\Node\TableNode|null $shareesList
	 */
	public function thenListOfSharees($shareeType, $isEmpty, $shareesList = null) {
		if ($isEmpty !== 'is empty') {
			$sharees = $shareesList->getRows();
			$respondedArray = $this->getArrayOfShareesResponded($this->response, $shareeType);
			PHPUnit_Framework_Assert::assertEquals($sharees, $respondedArray);
		} else {
			$respondedArray = $this->getArrayOfShareesResponded($this->response, $shareeType);
			PHPUnit_Framework_Assert::assertEmpty($respondedArray);
		}
	}

	public function getArrayOfShareesResponded(ResponseInterface $response, $shareeType) {
		$elements = $this->getResponseXml($response)->data;
		$elements = json_decode(json_encode($elements), 1);
		if (strpos($shareeType, 'exact ') === 0) {
			$elements = $elements['exact'];
			$shareeType = substr($shareeType, 6);
		}

		$sharees = [];
		foreach ($elements[$shareeType] as $element) {
			$sharees[] = [$element['label'], $element['value']['shareType'], $element['value']['shareWith']];
		}
		return $sharees;
	}

	protected function resetAppConfigs() {
		$this->modifyServerConfig('core', 'shareapi_only_share_with_group_members', 'no');
		$this->modifyServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');
		$this->modifyServerConfig('core', 'shareapi_share_dialog_user_enumeration_group_members', 'no');
		$this->modifyServerConfig('core', 'shareapi_allow_group_sharing', 'yes');
	}
}
