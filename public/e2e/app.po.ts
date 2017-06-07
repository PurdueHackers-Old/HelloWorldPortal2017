import { browser, by, element } from 'protractor';

export class HelloWorld2k17Page {
  navigateTo() {
    return browser.get('/');
  }

  getParagraphText() {
    return element(by.css('app-root h1')).getText();
  }
}
