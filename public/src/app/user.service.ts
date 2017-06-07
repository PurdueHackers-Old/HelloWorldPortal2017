import { Injectable } from '@angular/core';
import { Http, Headers, RequestOptions, Response } from '@angular/http';

import { User } from './user';

@Injectable()
export class UserService {

  constructor(private http: Http) { }

  create(user: User) {
      return this.http.post('http://localhost:8000'+'/api/user/register', user).map((response: Response) => response.json());
  }

  // private helper methods

  private jwt() {
      // create authorization header with jwt token
      let currentUser = JSON.parse(localStorage.getItem('currentUser'));
      if (currentUser && currentUser.token) {
          let headers = new Headers({ 'Authorization': 'Bearer ' + currentUser.token });
          return new RequestOptions({ headers: headers });
      }
  }

}
