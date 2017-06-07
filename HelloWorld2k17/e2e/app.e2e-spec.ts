import { HelloWorld2k17Page } from './app.po';

describe('hello-world2k17 App', () => {
  let page: HelloWorld2k17Page;

  beforeEach(() => {
    page = new HelloWorld2k17Page();
  });

  it('should display welcome message', done => {
    page.navigateTo();
    page.getParagraphText()
      .then(msg => expect(msg).toEqual('Welcome to app!!'))
      .then(done, done.fail);
  });
});
