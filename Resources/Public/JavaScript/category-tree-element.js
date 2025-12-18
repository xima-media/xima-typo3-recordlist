var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __knownSymbol = (name, symbol) => (symbol = Symbol[name]) ? symbol : Symbol.for("Symbol." + name);
var __typeError = (msg) => {
  throw TypeError(msg);
};
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
var __decoratorStart = (base) => [, , , __create(base?.[__knownSymbol("metadata")] ?? null)];
var __decoratorStrings = ["class", "method", "getter", "setter", "accessor", "field", "value", "get", "set"];
var __expectFn = (fn) => fn !== void 0 && typeof fn !== "function" ? __typeError("Function expected") : fn;
var __decoratorContext = (kind, name, done, metadata, fns) => ({ kind: __decoratorStrings[kind], name, metadata, addInitializer: (fn) => done._ ? __typeError("Already initialized") : fns.push(__expectFn(fn || null)) });
var __decoratorMetadata = (array, target) => __defNormalProp(target, __knownSymbol("metadata"), array[3]);
var __runInitializers = (array, flags, self, value) => {
  for (var i8 = 0, fns = array[flags >> 1], n7 = fns && fns.length; i8 < n7; i8++) flags & 1 ? fns[i8].call(self) : value = fns[i8].call(self, value);
  return value;
};
var __decorateElement = (array, flags, name, decorators, target, extra) => {
  var fn, it, done, ctx, access, k2 = flags & 7, s6 = !!(flags & 8), p3 = !!(flags & 16);
  var j2 = k2 > 3 ? array.length + 1 : k2 ? s6 ? 1 : 2 : 0, key = __decoratorStrings[k2 + 5];
  var initializers = k2 > 3 && (array[j2 - 1] = []), extraInitializers = array[j2] || (array[j2] = []);
  var desc = k2 && (!p3 && !s6 && (target = target.prototype), k2 < 5 && (k2 > 3 || !p3) && __getOwnPropDesc(k2 < 4 ? target : { get [name]() {
    return __privateGet(this, extra);
  }, set [name](x2) {
    return __privateSet(this, extra, x2);
  } }, name));
  k2 ? p3 && k2 < 4 && __name(extra, (k2 > 2 ? "set " : k2 > 1 ? "get " : "") + name) : __name(target, name);
  for (var i8 = decorators.length - 1; i8 >= 0; i8--) {
    ctx = __decoratorContext(k2, name, done = {}, array[3], extraInitializers);
    if (k2) {
      ctx.static = s6, ctx.private = p3, access = ctx.access = { has: p3 ? (x2) => __privateIn(target, x2) : (x2) => name in x2 };
      if (k2 ^ 3) access.get = p3 ? (x2) => (k2 ^ 1 ? __privateGet : __privateMethod)(x2, target, k2 ^ 4 ? extra : desc.get) : (x2) => x2[name];
      if (k2 > 2) access.set = p3 ? (x2, y3) => __privateSet(x2, target, y3, k2 ^ 4 ? extra : desc.set) : (x2, y3) => x2[name] = y3;
    }
    it = (0, decorators[i8])(k2 ? k2 < 4 ? p3 ? extra : desc[key] : k2 > 4 ? void 0 : { get: desc.get, set: desc.set } : target, ctx), done._ = 1;
    if (k2 ^ 4 || it === void 0) __expectFn(it) && (k2 > 4 ? initializers.unshift(it) : k2 ? p3 ? extra = it : desc[key] = it : target = it);
    else if (typeof it !== "object" || it === null) __typeError("Object expected");
    else __expectFn(fn = it.get) && (desc.get = fn), __expectFn(fn = it.set) && (desc.set = fn), __expectFn(fn = it.init) && initializers.unshift(fn);
  }
  return k2 || __decoratorMetadata(array, target), desc && __defProp(target, name, desc), p3 ? k2 ^ 4 ? extra : desc : target;
};
var __accessCheck = (obj, member, msg) => member.has(obj) || __typeError("Cannot " + msg);
var __privateIn = (member, obj) => Object(obj) !== obj ? __typeError('Cannot use the "in" operator on this value') : member.has(obj);
var __privateGet = (obj, member, getter) => (__accessCheck(obj, member, "read from private field"), getter ? getter.call(obj) : member.get(obj));
var __privateSet = (obj, member, value, setter) => (__accessCheck(obj, member, "write to private field"), setter ? setter.call(obj, value) : member.set(obj, value), value);
var __privateMethod = (obj, member, method) => (__accessCheck(obj, member, "access private method"), method);

// node_modules/@lit/reactive-element/css-tag.js
var t = globalThis;
var e = t.ShadowRoot && (void 0 === t.ShadyCSS || t.ShadyCSS.nativeShadow) && "adoptedStyleSheets" in Document.prototype && "replace" in CSSStyleSheet.prototype;
var s = Symbol();
var o = /* @__PURE__ */ new WeakMap();
var n = class {
  constructor(t6, e6, o6) {
    if (this._$cssResult$ = true, o6 !== s) throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");
    this.cssText = t6, this.t = e6;
  }
  get styleSheet() {
    let t6 = this.o;
    const s6 = this.t;
    if (e && void 0 === t6) {
      const e6 = void 0 !== s6 && 1 === s6.length;
      e6 && (t6 = o.get(s6)), void 0 === t6 && ((this.o = t6 = new CSSStyleSheet()).replaceSync(this.cssText), e6 && o.set(s6, t6));
    }
    return t6;
  }
  toString() {
    return this.cssText;
  }
};
var r = (t6) => new n("string" == typeof t6 ? t6 : t6 + "", void 0, s);
var S = (s6, o6) => {
  if (e) s6.adoptedStyleSheets = o6.map(((t6) => t6 instanceof CSSStyleSheet ? t6 : t6.styleSheet));
  else for (const e6 of o6) {
    const o7 = document.createElement("style"), n7 = t.litNonce;
    void 0 !== n7 && o7.setAttribute("nonce", n7), o7.textContent = e6.cssText, s6.appendChild(o7);
  }
};
var c = e ? (t6) => t6 : (t6) => t6 instanceof CSSStyleSheet ? ((t7) => {
  let e6 = "";
  for (const s6 of t7.cssRules) e6 += s6.cssText;
  return r(e6);
})(t6) : t6;

// node_modules/@lit/reactive-element/reactive-element.js
var { is: i2, defineProperty: e2, getOwnPropertyDescriptor: h, getOwnPropertyNames: r2, getOwnPropertySymbols: o2, getPrototypeOf: n2 } = Object;
var a = globalThis;
var c2 = a.trustedTypes;
var l = c2 ? c2.emptyScript : "";
var p = a.reactiveElementPolyfillSupport;
var d = (t6, s6) => t6;
var u = { toAttribute(t6, s6) {
  switch (s6) {
    case Boolean:
      t6 = t6 ? l : null;
      break;
    case Object:
    case Array:
      t6 = null == t6 ? t6 : JSON.stringify(t6);
  }
  return t6;
}, fromAttribute(t6, s6) {
  let i8 = t6;
  switch (s6) {
    case Boolean:
      i8 = null !== t6;
      break;
    case Number:
      i8 = null === t6 ? null : Number(t6);
      break;
    case Object:
    case Array:
      try {
        i8 = JSON.parse(t6);
      } catch (t7) {
        i8 = null;
      }
  }
  return i8;
} };
var f = (t6, s6) => !i2(t6, s6);
var b = { attribute: true, type: String, converter: u, reflect: false, useDefault: false, hasChanged: f };
Symbol.metadata ??= Symbol("metadata"), a.litPropertyMetadata ??= /* @__PURE__ */ new WeakMap();
var y = class extends HTMLElement {
  static addInitializer(t6) {
    this._$Ei(), (this.l ??= []).push(t6);
  }
  static get observedAttributes() {
    return this.finalize(), this._$Eh && [...this._$Eh.keys()];
  }
  static createProperty(t6, s6 = b) {
    if (s6.state && (s6.attribute = false), this._$Ei(), this.prototype.hasOwnProperty(t6) && ((s6 = Object.create(s6)).wrapped = true), this.elementProperties.set(t6, s6), !s6.noAccessor) {
      const i8 = Symbol(), h5 = this.getPropertyDescriptor(t6, i8, s6);
      void 0 !== h5 && e2(this.prototype, t6, h5);
    }
  }
  static getPropertyDescriptor(t6, s6, i8) {
    const { get: e6, set: r5 } = h(this.prototype, t6) ?? { get() {
      return this[s6];
    }, set(t7) {
      this[s6] = t7;
    } };
    return { get: e6, set(s7) {
      const h5 = e6?.call(this);
      r5?.call(this, s7), this.requestUpdate(t6, h5, i8);
    }, configurable: true, enumerable: true };
  }
  static getPropertyOptions(t6) {
    return this.elementProperties.get(t6) ?? b;
  }
  static _$Ei() {
    if (this.hasOwnProperty(d("elementProperties"))) return;
    const t6 = n2(this);
    t6.finalize(), void 0 !== t6.l && (this.l = [...t6.l]), this.elementProperties = new Map(t6.elementProperties);
  }
  static finalize() {
    if (this.hasOwnProperty(d("finalized"))) return;
    if (this.finalized = true, this._$Ei(), this.hasOwnProperty(d("properties"))) {
      const t7 = this.properties, s6 = [...r2(t7), ...o2(t7)];
      for (const i8 of s6) this.createProperty(i8, t7[i8]);
    }
    const t6 = this[Symbol.metadata];
    if (null !== t6) {
      const s6 = litPropertyMetadata.get(t6);
      if (void 0 !== s6) for (const [t7, i8] of s6) this.elementProperties.set(t7, i8);
    }
    this._$Eh = /* @__PURE__ */ new Map();
    for (const [t7, s6] of this.elementProperties) {
      const i8 = this._$Eu(t7, s6);
      void 0 !== i8 && this._$Eh.set(i8, t7);
    }
    this.elementStyles = this.finalizeStyles(this.styles);
  }
  static finalizeStyles(s6) {
    const i8 = [];
    if (Array.isArray(s6)) {
      const e6 = new Set(s6.flat(1 / 0).reverse());
      for (const s7 of e6) i8.unshift(c(s7));
    } else void 0 !== s6 && i8.push(c(s6));
    return i8;
  }
  static _$Eu(t6, s6) {
    const i8 = s6.attribute;
    return false === i8 ? void 0 : "string" == typeof i8 ? i8 : "string" == typeof t6 ? t6.toLowerCase() : void 0;
  }
  constructor() {
    super(), this._$Ep = void 0, this.isUpdatePending = false, this.hasUpdated = false, this._$Em = null, this._$Ev();
  }
  _$Ev() {
    this._$ES = new Promise(((t6) => this.enableUpdating = t6)), this._$AL = /* @__PURE__ */ new Map(), this._$E_(), this.requestUpdate(), this.constructor.l?.forEach(((t6) => t6(this)));
  }
  addController(t6) {
    (this._$EO ??= /* @__PURE__ */ new Set()).add(t6), void 0 !== this.renderRoot && this.isConnected && t6.hostConnected?.();
  }
  removeController(t6) {
    this._$EO?.delete(t6);
  }
  _$E_() {
    const t6 = /* @__PURE__ */ new Map(), s6 = this.constructor.elementProperties;
    for (const i8 of s6.keys()) this.hasOwnProperty(i8) && (t6.set(i8, this[i8]), delete this[i8]);
    t6.size > 0 && (this._$Ep = t6);
  }
  createRenderRoot() {
    const t6 = this.shadowRoot ?? this.attachShadow(this.constructor.shadowRootOptions);
    return S(t6, this.constructor.elementStyles), t6;
  }
  connectedCallback() {
    this.renderRoot ??= this.createRenderRoot(), this.enableUpdating(true), this._$EO?.forEach(((t6) => t6.hostConnected?.()));
  }
  enableUpdating(t6) {
  }
  disconnectedCallback() {
    this._$EO?.forEach(((t6) => t6.hostDisconnected?.()));
  }
  attributeChangedCallback(t6, s6, i8) {
    this._$AK(t6, i8);
  }
  _$ET(t6, s6) {
    const i8 = this.constructor.elementProperties.get(t6), e6 = this.constructor._$Eu(t6, i8);
    if (void 0 !== e6 && true === i8.reflect) {
      const h5 = (void 0 !== i8.converter?.toAttribute ? i8.converter : u).toAttribute(s6, i8.type);
      this._$Em = t6, null == h5 ? this.removeAttribute(e6) : this.setAttribute(e6, h5), this._$Em = null;
    }
  }
  _$AK(t6, s6) {
    const i8 = this.constructor, e6 = i8._$Eh.get(t6);
    if (void 0 !== e6 && this._$Em !== e6) {
      const t7 = i8.getPropertyOptions(e6), h5 = "function" == typeof t7.converter ? { fromAttribute: t7.converter } : void 0 !== t7.converter?.fromAttribute ? t7.converter : u;
      this._$Em = e6;
      const r5 = h5.fromAttribute(s6, t7.type);
      this[e6] = r5 ?? this._$Ej?.get(e6) ?? r5, this._$Em = null;
    }
  }
  requestUpdate(t6, s6, i8) {
    if (void 0 !== t6) {
      const e6 = this.constructor, h5 = this[t6];
      if (i8 ??= e6.getPropertyOptions(t6), !((i8.hasChanged ?? f)(h5, s6) || i8.useDefault && i8.reflect && h5 === this._$Ej?.get(t6) && !this.hasAttribute(e6._$Eu(t6, i8)))) return;
      this.C(t6, s6, i8);
    }
    false === this.isUpdatePending && (this._$ES = this._$EP());
  }
  C(t6, s6, { useDefault: i8, reflect: e6, wrapped: h5 }, r5) {
    i8 && !(this._$Ej ??= /* @__PURE__ */ new Map()).has(t6) && (this._$Ej.set(t6, r5 ?? s6 ?? this[t6]), true !== h5 || void 0 !== r5) || (this._$AL.has(t6) || (this.hasUpdated || i8 || (s6 = void 0), this._$AL.set(t6, s6)), true === e6 && this._$Em !== t6 && (this._$Eq ??= /* @__PURE__ */ new Set()).add(t6));
  }
  async _$EP() {
    this.isUpdatePending = true;
    try {
      await this._$ES;
    } catch (t7) {
      Promise.reject(t7);
    }
    const t6 = this.scheduleUpdate();
    return null != t6 && await t6, !this.isUpdatePending;
  }
  scheduleUpdate() {
    return this.performUpdate();
  }
  performUpdate() {
    if (!this.isUpdatePending) return;
    if (!this.hasUpdated) {
      if (this.renderRoot ??= this.createRenderRoot(), this._$Ep) {
        for (const [t8, s7] of this._$Ep) this[t8] = s7;
        this._$Ep = void 0;
      }
      const t7 = this.constructor.elementProperties;
      if (t7.size > 0) for (const [s7, i8] of t7) {
        const { wrapped: t8 } = i8, e6 = this[s7];
        true !== t8 || this._$AL.has(s7) || void 0 === e6 || this.C(s7, void 0, i8, e6);
      }
    }
    let t6 = false;
    const s6 = this._$AL;
    try {
      t6 = this.shouldUpdate(s6), t6 ? (this.willUpdate(s6), this._$EO?.forEach(((t7) => t7.hostUpdate?.())), this.update(s6)) : this._$EM();
    } catch (s7) {
      throw t6 = false, this._$EM(), s7;
    }
    t6 && this._$AE(s6);
  }
  willUpdate(t6) {
  }
  _$AE(t6) {
    this._$EO?.forEach(((t7) => t7.hostUpdated?.())), this.hasUpdated || (this.hasUpdated = true, this.firstUpdated(t6)), this.updated(t6);
  }
  _$EM() {
    this._$AL = /* @__PURE__ */ new Map(), this.isUpdatePending = false;
  }
  get updateComplete() {
    return this.getUpdateComplete();
  }
  getUpdateComplete() {
    return this._$ES;
  }
  shouldUpdate(t6) {
    return true;
  }
  update(t6) {
    this._$Eq &&= this._$Eq.forEach(((t7) => this._$ET(t7, this[t7]))), this._$EM();
  }
  updated(t6) {
  }
  firstUpdated(t6) {
  }
};
y.elementStyles = [], y.shadowRootOptions = { mode: "open" }, y[d("elementProperties")] = /* @__PURE__ */ new Map(), y[d("finalized")] = /* @__PURE__ */ new Map(), p?.({ ReactiveElement: y }), (a.reactiveElementVersions ??= []).push("2.1.1");

// node_modules/lit-html/lit-html.js
var t2 = globalThis;
var i3 = t2.trustedTypes;
var s2 = i3 ? i3.createPolicy("lit-html", { createHTML: (t6) => t6 }) : void 0;
var e3 = "$lit$";
var h2 = `lit$${Math.random().toFixed(9).slice(2)}$`;
var o3 = "?" + h2;
var n3 = `<${o3}>`;
var r3 = document;
var l2 = () => r3.createComment("");
var c3 = (t6) => null === t6 || "object" != typeof t6 && "function" != typeof t6;
var a2 = Array.isArray;
var u2 = (t6) => a2(t6) || "function" == typeof t6?.[Symbol.iterator];
var d2 = "[ 	\n\f\r]";
var f2 = /<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g;
var v = /-->/g;
var _ = />/g;
var m = RegExp(`>|${d2}(?:([^\\s"'>=/]+)(${d2}*=${d2}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`, "g");
var p2 = /'/g;
var g = /"/g;
var $ = /^(?:script|style|textarea|title)$/i;
var y2 = (t6) => (i8, ...s6) => ({ _$litType$: t6, strings: i8, values: s6 });
var x = y2(1);
var b2 = y2(2);
var w = y2(3);
var T = Symbol.for("lit-noChange");
var E = Symbol.for("lit-nothing");
var A = /* @__PURE__ */ new WeakMap();
var C = r3.createTreeWalker(r3, 129);
function P(t6, i8) {
  if (!a2(t6) || !t6.hasOwnProperty("raw")) throw Error("invalid template strings array");
  return void 0 !== s2 ? s2.createHTML(i8) : i8;
}
var V = (t6, i8) => {
  const s6 = t6.length - 1, o6 = [];
  let r5, l3 = 2 === i8 ? "<svg>" : 3 === i8 ? "<math>" : "", c6 = f2;
  for (let i9 = 0; i9 < s6; i9++) {
    const s7 = t6[i9];
    let a3, u3, d3 = -1, y3 = 0;
    for (; y3 < s7.length && (c6.lastIndex = y3, u3 = c6.exec(s7), null !== u3); ) y3 = c6.lastIndex, c6 === f2 ? "!--" === u3[1] ? c6 = v : void 0 !== u3[1] ? c6 = _ : void 0 !== u3[2] ? ($.test(u3[2]) && (r5 = RegExp("</" + u3[2], "g")), c6 = m) : void 0 !== u3[3] && (c6 = m) : c6 === m ? ">" === u3[0] ? (c6 = r5 ?? f2, d3 = -1) : void 0 === u3[1] ? d3 = -2 : (d3 = c6.lastIndex - u3[2].length, a3 = u3[1], c6 = void 0 === u3[3] ? m : '"' === u3[3] ? g : p2) : c6 === g || c6 === p2 ? c6 = m : c6 === v || c6 === _ ? c6 = f2 : (c6 = m, r5 = void 0);
    const x2 = c6 === m && t6[i9 + 1].startsWith("/>") ? " " : "";
    l3 += c6 === f2 ? s7 + n3 : d3 >= 0 ? (o6.push(a3), s7.slice(0, d3) + e3 + s7.slice(d3) + h2 + x2) : s7 + h2 + (-2 === d3 ? i9 : x2);
  }
  return [P(t6, l3 + (t6[s6] || "<?>") + (2 === i8 ? "</svg>" : 3 === i8 ? "</math>" : "")), o6];
};
var N = class _N {
  constructor({ strings: t6, _$litType$: s6 }, n7) {
    let r5;
    this.parts = [];
    let c6 = 0, a3 = 0;
    const u3 = t6.length - 1, d3 = this.parts, [f5, v2] = V(t6, s6);
    if (this.el = _N.createElement(f5, n7), C.currentNode = this.el.content, 2 === s6 || 3 === s6) {
      const t7 = this.el.content.firstChild;
      t7.replaceWith(...t7.childNodes);
    }
    for (; null !== (r5 = C.nextNode()) && d3.length < u3; ) {
      if (1 === r5.nodeType) {
        if (r5.hasAttributes()) for (const t7 of r5.getAttributeNames()) if (t7.endsWith(e3)) {
          const i8 = v2[a3++], s7 = r5.getAttribute(t7).split(h2), e6 = /([.?@])?(.*)/.exec(i8);
          d3.push({ type: 1, index: c6, name: e6[2], strings: s7, ctor: "." === e6[1] ? H : "?" === e6[1] ? I : "@" === e6[1] ? L : k }), r5.removeAttribute(t7);
        } else t7.startsWith(h2) && (d3.push({ type: 6, index: c6 }), r5.removeAttribute(t7));
        if ($.test(r5.tagName)) {
          const t7 = r5.textContent.split(h2), s7 = t7.length - 1;
          if (s7 > 0) {
            r5.textContent = i3 ? i3.emptyScript : "";
            for (let i8 = 0; i8 < s7; i8++) r5.append(t7[i8], l2()), C.nextNode(), d3.push({ type: 2, index: ++c6 });
            r5.append(t7[s7], l2());
          }
        }
      } else if (8 === r5.nodeType) if (r5.data === o3) d3.push({ type: 2, index: c6 });
      else {
        let t7 = -1;
        for (; -1 !== (t7 = r5.data.indexOf(h2, t7 + 1)); ) d3.push({ type: 7, index: c6 }), t7 += h2.length - 1;
      }
      c6++;
    }
  }
  static createElement(t6, i8) {
    const s6 = r3.createElement("template");
    return s6.innerHTML = t6, s6;
  }
};
function S2(t6, i8, s6 = t6, e6) {
  if (i8 === T) return i8;
  let h5 = void 0 !== e6 ? s6._$Co?.[e6] : s6._$Cl;
  const o6 = c3(i8) ? void 0 : i8._$litDirective$;
  return h5?.constructor !== o6 && (h5?._$AO?.(false), void 0 === o6 ? h5 = void 0 : (h5 = new o6(t6), h5._$AT(t6, s6, e6)), void 0 !== e6 ? (s6._$Co ??= [])[e6] = h5 : s6._$Cl = h5), void 0 !== h5 && (i8 = S2(t6, h5._$AS(t6, i8.values), h5, e6)), i8;
}
var M = class {
  constructor(t6, i8) {
    this._$AV = [], this._$AN = void 0, this._$AD = t6, this._$AM = i8;
  }
  get parentNode() {
    return this._$AM.parentNode;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  u(t6) {
    const { el: { content: i8 }, parts: s6 } = this._$AD, e6 = (t6?.creationScope ?? r3).importNode(i8, true);
    C.currentNode = e6;
    let h5 = C.nextNode(), o6 = 0, n7 = 0, l3 = s6[0];
    for (; void 0 !== l3; ) {
      if (o6 === l3.index) {
        let i9;
        2 === l3.type ? i9 = new R(h5, h5.nextSibling, this, t6) : 1 === l3.type ? i9 = new l3.ctor(h5, l3.name, l3.strings, this, t6) : 6 === l3.type && (i9 = new z(h5, this, t6)), this._$AV.push(i9), l3 = s6[++n7];
      }
      o6 !== l3?.index && (h5 = C.nextNode(), o6++);
    }
    return C.currentNode = r3, e6;
  }
  p(t6) {
    let i8 = 0;
    for (const s6 of this._$AV) void 0 !== s6 && (void 0 !== s6.strings ? (s6._$AI(t6, s6, i8), i8 += s6.strings.length - 2) : s6._$AI(t6[i8])), i8++;
  }
};
var R = class _R {
  get _$AU() {
    return this._$AM?._$AU ?? this._$Cv;
  }
  constructor(t6, i8, s6, e6) {
    this.type = 2, this._$AH = E, this._$AN = void 0, this._$AA = t6, this._$AB = i8, this._$AM = s6, this.options = e6, this._$Cv = e6?.isConnected ?? true;
  }
  get parentNode() {
    let t6 = this._$AA.parentNode;
    const i8 = this._$AM;
    return void 0 !== i8 && 11 === t6?.nodeType && (t6 = i8.parentNode), t6;
  }
  get startNode() {
    return this._$AA;
  }
  get endNode() {
    return this._$AB;
  }
  _$AI(t6, i8 = this) {
    t6 = S2(this, t6, i8), c3(t6) ? t6 === E || null == t6 || "" === t6 ? (this._$AH !== E && this._$AR(), this._$AH = E) : t6 !== this._$AH && t6 !== T && this._(t6) : void 0 !== t6._$litType$ ? this.$(t6) : void 0 !== t6.nodeType ? this.T(t6) : u2(t6) ? this.k(t6) : this._(t6);
  }
  O(t6) {
    return this._$AA.parentNode.insertBefore(t6, this._$AB);
  }
  T(t6) {
    this._$AH !== t6 && (this._$AR(), this._$AH = this.O(t6));
  }
  _(t6) {
    this._$AH !== E && c3(this._$AH) ? this._$AA.nextSibling.data = t6 : this.T(r3.createTextNode(t6)), this._$AH = t6;
  }
  $(t6) {
    const { values: i8, _$litType$: s6 } = t6, e6 = "number" == typeof s6 ? this._$AC(t6) : (void 0 === s6.el && (s6.el = N.createElement(P(s6.h, s6.h[0]), this.options)), s6);
    if (this._$AH?._$AD === e6) this._$AH.p(i8);
    else {
      const t7 = new M(e6, this), s7 = t7.u(this.options);
      t7.p(i8), this.T(s7), this._$AH = t7;
    }
  }
  _$AC(t6) {
    let i8 = A.get(t6.strings);
    return void 0 === i8 && A.set(t6.strings, i8 = new N(t6)), i8;
  }
  k(t6) {
    a2(this._$AH) || (this._$AH = [], this._$AR());
    const i8 = this._$AH;
    let s6, e6 = 0;
    for (const h5 of t6) e6 === i8.length ? i8.push(s6 = new _R(this.O(l2()), this.O(l2()), this, this.options)) : s6 = i8[e6], s6._$AI(h5), e6++;
    e6 < i8.length && (this._$AR(s6 && s6._$AB.nextSibling, e6), i8.length = e6);
  }
  _$AR(t6 = this._$AA.nextSibling, i8) {
    for (this._$AP?.(false, true, i8); t6 !== this._$AB; ) {
      const i9 = t6.nextSibling;
      t6.remove(), t6 = i9;
    }
  }
  setConnected(t6) {
    void 0 === this._$AM && (this._$Cv = t6, this._$AP?.(t6));
  }
};
var k = class {
  get tagName() {
    return this.element.tagName;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  constructor(t6, i8, s6, e6, h5) {
    this.type = 1, this._$AH = E, this._$AN = void 0, this.element = t6, this.name = i8, this._$AM = e6, this.options = h5, s6.length > 2 || "" !== s6[0] || "" !== s6[1] ? (this._$AH = Array(s6.length - 1).fill(new String()), this.strings = s6) : this._$AH = E;
  }
  _$AI(t6, i8 = this, s6, e6) {
    const h5 = this.strings;
    let o6 = false;
    if (void 0 === h5) t6 = S2(this, t6, i8, 0), o6 = !c3(t6) || t6 !== this._$AH && t6 !== T, o6 && (this._$AH = t6);
    else {
      const e7 = t6;
      let n7, r5;
      for (t6 = h5[0], n7 = 0; n7 < h5.length - 1; n7++) r5 = S2(this, e7[s6 + n7], i8, n7), r5 === T && (r5 = this._$AH[n7]), o6 ||= !c3(r5) || r5 !== this._$AH[n7], r5 === E ? t6 = E : t6 !== E && (t6 += (r5 ?? "") + h5[n7 + 1]), this._$AH[n7] = r5;
    }
    o6 && !e6 && this.j(t6);
  }
  j(t6) {
    t6 === E ? this.element.removeAttribute(this.name) : this.element.setAttribute(this.name, t6 ?? "");
  }
};
var H = class extends k {
  constructor() {
    super(...arguments), this.type = 3;
  }
  j(t6) {
    this.element[this.name] = t6 === E ? void 0 : t6;
  }
};
var I = class extends k {
  constructor() {
    super(...arguments), this.type = 4;
  }
  j(t6) {
    this.element.toggleAttribute(this.name, !!t6 && t6 !== E);
  }
};
var L = class extends k {
  constructor(t6, i8, s6, e6, h5) {
    super(t6, i8, s6, e6, h5), this.type = 5;
  }
  _$AI(t6, i8 = this) {
    if ((t6 = S2(this, t6, i8, 0) ?? E) === T) return;
    const s6 = this._$AH, e6 = t6 === E && s6 !== E || t6.capture !== s6.capture || t6.once !== s6.once || t6.passive !== s6.passive, h5 = t6 !== E && (s6 === E || e6);
    e6 && this.element.removeEventListener(this.name, this, s6), h5 && this.element.addEventListener(this.name, this, t6), this._$AH = t6;
  }
  handleEvent(t6) {
    "function" == typeof this._$AH ? this._$AH.call(this.options?.host ?? this.element, t6) : this._$AH.handleEvent(t6);
  }
};
var z = class {
  constructor(t6, i8, s6) {
    this.element = t6, this.type = 6, this._$AN = void 0, this._$AM = i8, this.options = s6;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  _$AI(t6) {
    S2(this, t6);
  }
};
var Z = { M: e3, P: h2, A: o3, C: 1, L: V, R: M, D: u2, V: S2, I: R, H: k, N: I, U: L, B: H, F: z };
var j = t2.litHtmlPolyfillSupport;
j?.(N, R), (t2.litHtmlVersions ??= []).push("3.3.1");
var B = (t6, i8, s6) => {
  const e6 = s6?.renderBefore ?? i8;
  let h5 = e6._$litPart$;
  if (void 0 === h5) {
    const t7 = s6?.renderBefore ?? null;
    e6._$litPart$ = h5 = new R(i8.insertBefore(l2(), t7), t7, void 0, s6 ?? {});
  }
  return h5._$AI(t6), h5;
};

// node_modules/lit-element/lit-element.js
var s3 = globalThis;
var i4 = class extends y {
  constructor() {
    super(...arguments), this.renderOptions = { host: this }, this._$Do = void 0;
  }
  createRenderRoot() {
    const t6 = super.createRenderRoot();
    return this.renderOptions.renderBefore ??= t6.firstChild, t6;
  }
  update(t6) {
    const r5 = this.render();
    this.hasUpdated || (this.renderOptions.isConnected = this.isConnected), super.update(t6), this._$Do = B(r5, this.renderRoot, this.renderOptions);
  }
  connectedCallback() {
    super.connectedCallback(), this._$Do?.setConnected(true);
  }
  disconnectedCallback() {
    super.disconnectedCallback(), this._$Do?.setConnected(false);
  }
  render() {
    return T;
  }
};
i4._$litElement$ = true, i4["finalized"] = true, s3.litElementHydrateSupport?.({ LitElement: i4 });
var o4 = s3.litElementPolyfillSupport;
o4?.({ LitElement: i4 });
(s3.litElementVersions ??= []).push("4.2.1");

// node_modules/@lit/reactive-element/decorators/custom-element.js
var t3 = (t6) => (e6, o6) => {
  void 0 !== o6 ? o6.addInitializer((() => {
    customElements.define(t6, e6);
  })) : customElements.define(t6, e6);
};

// node_modules/lit-html/directive-helpers.js
var { I: t4 } = Z;
var i5 = (o6) => null === o6 || "object" != typeof o6 && "function" != typeof o6;
var f3 = (o6) => void 0 === o6.strings;

// node_modules/lit-html/directive.js
var t5 = { ATTRIBUTE: 1, CHILD: 2, PROPERTY: 3, BOOLEAN_ATTRIBUTE: 4, EVENT: 5, ELEMENT: 6 };
var e5 = (t6) => (...e6) => ({ _$litDirective$: t6, values: e6 });
var i6 = class {
  constructor(t6) {
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  _$AT(t6, e6, i8) {
    this._$Ct = t6, this._$AM = e6, this._$Ci = i8;
  }
  _$AS(t6, e6) {
    return this.update(t6, e6);
  }
  update(t6, e6) {
    return this.render(...e6);
  }
};

// node_modules/lit-html/async-directive.js
var s4 = (i8, t6) => {
  const e6 = i8._$AN;
  if (void 0 === e6) return false;
  for (const i9 of e6) i9._$AO?.(t6, false), s4(i9, t6);
  return true;
};
var o5 = (i8) => {
  let t6, e6;
  do {
    if (void 0 === (t6 = i8._$AM)) break;
    e6 = t6._$AN, e6.delete(i8), i8 = t6;
  } while (0 === e6?.size);
};
var r4 = (i8) => {
  for (let t6; t6 = i8._$AM; i8 = t6) {
    let e6 = t6._$AN;
    if (void 0 === e6) t6._$AN = e6 = /* @__PURE__ */ new Set();
    else if (e6.has(i8)) break;
    e6.add(i8), c4(t6);
  }
};
function h3(i8) {
  void 0 !== this._$AN ? (o5(this), this._$AM = i8, r4(this)) : this._$AM = i8;
}
function n5(i8, t6 = false, e6 = 0) {
  const r5 = this._$AH, h5 = this._$AN;
  if (void 0 !== h5 && 0 !== h5.size) if (t6) if (Array.isArray(r5)) for (let i9 = e6; i9 < r5.length; i9++) s4(r5[i9], false), o5(r5[i9]);
  else null != r5 && (s4(r5, false), o5(r5));
  else s4(this, i8);
}
var c4 = (i8) => {
  i8.type == t5.CHILD && (i8._$AP ??= n5, i8._$AQ ??= h3);
};
var f4 = class extends i6 {
  constructor() {
    super(...arguments), this._$AN = void 0;
  }
  _$AT(i8, t6, e6) {
    super._$AT(i8, t6, e6), r4(this), this.isConnected = i8._$AU;
  }
  _$AO(i8, t6 = true) {
    i8 !== this.isConnected && (this.isConnected = i8, i8 ? this.reconnected?.() : this.disconnected?.()), t6 && (s4(this, i8), o5(this));
  }
  setValue(t6) {
    if (f3(this._$Ct)) this._$Ct._$AI(t6, this);
    else {
      const i8 = [...this._$Ct._$AH];
      i8[this._$Ci] = t6, this._$Ct._$AI(i8, this, 0);
    }
  }
  disconnected() {
  }
  reconnected() {
  }
};

// node_modules/lit-html/directives/private-async-helpers.js
var s5 = class {
  constructor(t6) {
    this.G = t6;
  }
  disconnect() {
    this.G = void 0;
  }
  reconnect(t6) {
    this.G = t6;
  }
  deref() {
    return this.G;
  }
};
var i7 = class {
  constructor() {
    this.Y = void 0, this.Z = void 0;
  }
  get() {
    return this.Y;
  }
  pause() {
    this.Y ??= new Promise(((t6) => this.Z = t6));
  }
  resume() {
    this.Z?.(), this.Y = this.Z = void 0;
  }
};

// node_modules/lit-html/directives/until.js
var n6 = (t6) => !i5(t6) && "function" == typeof t6.then;
var h4 = 1073741823;
var c5 = class extends f4 {
  constructor() {
    super(...arguments), this._$Cwt = h4, this._$Cbt = [], this._$CK = new s5(this), this._$CX = new i7();
  }
  render(...s6) {
    return s6.find(((t6) => !n6(t6))) ?? T;
  }
  update(s6, i8) {
    const e6 = this._$Cbt;
    let r5 = e6.length;
    this._$Cbt = i8;
    const o6 = this._$CK, c6 = this._$CX;
    this.isConnected || this.disconnected();
    for (let t6 = 0; t6 < i8.length && !(t6 > this._$Cwt); t6++) {
      const s7 = i8[t6];
      if (!n6(s7)) return this._$Cwt = t6, s7;
      t6 < r5 && s7 === e6[t6] || (this._$Cwt = h4, r5 = 0, Promise.resolve(s7).then((async (t7) => {
        for (; c6.get(); ) await c6.get();
        const i9 = o6.deref();
        if (void 0 !== i9) {
          const e7 = i9._$Cbt.indexOf(s7);
          e7 > -1 && e7 < i9._$Cwt && (i9._$Cwt = e7, i9.setValue(t7));
        }
      })));
    }
    return T;
  }
  disconnected() {
    this._$CK.disconnect(), this._$CX.pause();
  }
  reconnected() {
    this._$CK.reconnect(this), this._$CX.resume();
  }
};
var m2 = e5(c5);

// Resources/Private/TypeScript/category-tree-element.ts
import { lll } from "@typo3/core/lit-helper.js";
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Persistent from "@typo3/backend/storage/persistent.js";
import { ModuleUtility } from "@typo3/backend/module.js";
import ContextMenu from "@typo3/backend/context-menu.js";
import { PageTree } from "@typo3/backend/tree/page-tree.js";
import { TreeNodeCommandEnum, TreeNodePositionEnum } from "@typo3/backend/tree/tree-node.js";
import { TreeToolbar } from "@typo3/backend/tree/tree-toolbar.js";
import { TreeModuleState } from "@typo3/backend/tree/tree-module-state.js";
import Modal from "@typo3/backend/modal.js";
import Severity from "@typo3/backend/severity.js";
import { ModuleStateStorage } from "@typo3/backend/storage/module-state-storage.js";
import { DataTransferTypes } from "@typo3/backend/enum/data-transfer-types.js";
var navigationComponentName = "typo3-backend-navigation-component-categorytree";
var _EditablePageTree_decorators, _init, _a;
_EditablePageTree_decorators = [t3("typo3-backend-navigation-component-categorytree-tree")];
var EditablePageTree = class extends (_a = PageTree) {
  constructor() {
    super(...arguments);
    this.allowNodeEdit = true;
    this.allowNodeDrag = true;
    this.allowNodeSorting = true;
    this.mountPointPath = null;
  }
  sendChangeCommand(data) {
    let params = "";
    let targetUid = "0";
    if (data.target) {
      targetUid = data.target.identifier;
      if (data.position === TreeNodePositionEnum.BEFORE) {
        const previousNode = this.getPreviousNode(data.target);
        targetUid = (previousNode.depth === data.target.depth ? "-" : "") + previousNode.identifier;
      } else if (data.position === TreeNodePositionEnum.AFTER) {
        targetUid = "-" + targetUid;
      }
    }
    if (data.command === TreeNodeCommandEnum.NEW) {
      const newData = data;
      params = "&data[pages][" + data.node.identifier + "][pid]=" + encodeURIComponent(targetUid) + "&data[pages][" + data.node.identifier + "][title]=" + encodeURIComponent(newData.title) + "&data[pages][" + data.node.identifier + "][doktype]=" + encodeURIComponent(newData.doktype);
    } else if (data.command === TreeNodeCommandEnum.EDIT) {
      params = "&data[pages][" + data.node.identifier + "][title]=" + encodeURIComponent(data.title);
    } else if (data.command === TreeNodeCommandEnum.DELETE) {
      const moduleStateStorage = ModuleStateStorage.current("web");
      if (data.node.identifier === moduleStateStorage.identifier) {
        this.selectFirstNode();
      }
      params = "&cmd[pages][" + data.node.identifier + "][delete]=1";
    } else {
      params = "cmd[pages][" + data.node.identifier + "][" + data.command + "]=" + targetUid;
    }
    this.requestTreeUpdate(params).then((response) => {
      if (response && response.hasErrors) {
        this.errorNotification(response.messages);
      } else {
        if (data.command === TreeNodeCommandEnum.NEW) {
          const parentNode = this.getParentNode(data.node);
          parentNode.loaded = false;
          this.loadChildren(parentNode);
        } else {
          this.refreshOrFilterTree();
        }
      }
    });
  }
  /**
  * Initializes a drag&drop when called on the page tree. Should be moved somewhere else at some point
  */
  initializeDragForNode() {
    throw new Error("unused");
  }
  async handleNodeEdit(node, newName) {
    node.__loading = true;
    if (node.identifier.startsWith("NEW")) {
      const target = this.getPreviousNode(node);
      const position = node.depth === target.depth ? TreeNodePositionEnum.AFTER : TreeNodePositionEnum.INSIDE;
      const options = {
        command: TreeNodeCommandEnum.NEW,
        node,
        title: newName,
        position,
        target,
        doktype: node.doktype
      };
      await this.sendChangeCommand(options);
    } else {
      const options = {
        command: TreeNodeCommandEnum.EDIT,
        node,
        title: newName
      };
      await this.sendChangeCommand(options);
    }
    node.__loading = false;
  }
  createDataTransferItemsFromNode(node) {
    return [
      {
        type: DataTransferTypes.treenode,
        data: this.getNodeTreeIdentifier(node)
      },
      {
        type: DataTransferTypes.pages,
        data: JSON.stringify({
          records: [
            {
              identifier: node.identifier,
              tablename: "pages"
            }
          ]
        })
      }
    ];
  }
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  async handleNodeAdd(node, target, position) {
    this.updateComplete.then(() => {
      this.editNode(node);
    });
  }
  handleNodeDelete(node) {
    const options = {
      node,
      command: TreeNodeCommandEnum.DELETE
    };
    if (this.settings.displayDeleteConfirmation) {
      const modal = Modal.confirm(
        TYPO3.lang["mess.delete.title"],
        TYPO3.lang["mess.delete"].replace("%s", options.node.name),
        Severity.warning,
        [
          {
            text: TYPO3.lang["labels.cancel"] || "Cancel",
            active: true,
            btnClass: "btn-default",
            name: "cancel"
          },
          {
            text: TYPO3.lang.delete || "Delete",
            btnClass: "btn-warning",
            name: "delete"
          }
        ]
      );
      modal.addEventListener("button.clicked", (e6) => {
        const target = e6.target;
        if (target.name === "delete") {
          this.sendChangeCommand(options);
        }
        Modal.dismiss();
      });
    } else {
      this.sendChangeCommand(options);
    }
  }
  handleNodeMove(node, target, position) {
    const options = {
      node,
      target,
      position,
      command: TreeNodeCommandEnum.MOVE
    };
    let modalText = "";
    switch (position) {
      case TreeNodePositionEnum.BEFORE:
        modalText = TYPO3.lang["mess.move_before"];
        break;
      case TreeNodePositionEnum.AFTER:
        modalText = TYPO3.lang["mess.move_after"];
        break;
      default:
        modalText = TYPO3.lang["mess.move_into"];
        break;
    }
    modalText = modalText.replace("%s", node.name).replace("%s", target.name);
    const modal = Modal.confirm(
      TYPO3.lang.move_page,
      modalText,
      Severity.warning,
      [
        {
          text: TYPO3.lang["labels.cancel"] || "Cancel",
          active: true,
          btnClass: "btn-default",
          name: "cancel"
        },
        {
          text: TYPO3.lang["cm.copy"] || "Copy",
          btnClass: "btn-warning",
          name: "copy"
        },
        {
          text: TYPO3.lang["labels.move"] || "Move",
          btnClass: "btn-warning",
          name: "move"
        }
      ]
    );
    modal.addEventListener("button.clicked", (e6) => {
      const target2 = e6.target;
      if (target2.name === "move") {
        options.command = TreeNodeCommandEnum.MOVE;
        this.sendChangeCommand(options);
      } else if (target2.name === "copy") {
        options.command = TreeNodeCommandEnum.COPY;
        this.sendChangeCommand(options);
      }
      modal.hideModal();
    });
  }
  requestTreeUpdate(params) {
    return new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process).post(params, {
      headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" }
    }).then((response) => {
      return response.resolve();
    }).catch((error) => {
      this.errorNotification(error);
      this.loadData();
    });
  }
};
_init = __decoratorStart(_a);
EditablePageTree = __decorateElement(_init, 0, "EditablePageTree", _EditablePageTree_decorators, EditablePageTree);
__runInitializers(_init, 1, EditablePageTree);
var _PageTreeNavigationComponent_decorators, _init2, _a2;
_PageTreeNavigationComponent_decorators = [t3("typo3-backend-navigation-component-categorytree")];
var PageTreeNavigationComponent = class extends (_a2 = TreeModuleState(i4)) {
  constructor() {
    super(...arguments);
    this.tree = void 0;
    this.mountPointPath = null;
    this.moduleStateType = "web";
    this.configuration = null;
    this.refresh = () => {
      this.tree.refreshOrFilterTree();
    };
    this.setMountPoint = (e6) => {
      this.setTemporaryMountPoint(e6.detail.pageId);
    };
    this.selectFirstNode = () => {
      this.tree.selectFirstNode();
    };
    this.loadContent = (evt) => {
      const node = evt.detail.node;
      if (!node?.checked) {
        return;
      }
      ModuleStateStorage.updateWithTreeIdentifier("web", node.identifier, node.__treeIdentifier);
      if (evt.detail.propagate === false) {
        return;
      }
      const moduleMenu = top.TYPO3.ModuleMenu.App;
      let contentUrl = ModuleUtility.getFromName(moduleMenu.getCurrentModule()).link;
      contentUrl += contentUrl.includes("?") ? "&" : "?";
      top.TYPO3.Backend.ContentContainer.setUrl(contentUrl + "id=" + node.identifier);
    };
    this.showContextMenu = (evt) => {
      const node = evt.detail.node;
      if (!node) {
        return;
      }
      ContextMenu.show(
        node.recordType,
        node.identifier,
        "tree",
        "",
        "",
        this.tree.getElementFromNode(node),
        evt.detail.originalEvent
      );
    };
  }
  connectedCallback() {
    super.connectedCallback();
    document.addEventListener("typo3:pagetree:refresh", this.refresh);
    document.addEventListener("typo3:pagetree:mountPoint", this.setMountPoint);
    document.addEventListener("typo3:pagetree:selectFirstNode", this.selectFirstNode);
  }
  disconnectedCallback() {
    document.removeEventListener("typo3:pagetree:refresh", this.refresh);
    document.removeEventListener("typo3:pagetree:mountPoint", this.setMountPoint);
    document.removeEventListener("typo3:pagetree:selectFirstNode", this.selectFirstNode);
    super.disconnectedCallback();
  }
  // disable shadow dom for now
  createRenderRoot() {
    return this;
  }
  render() {
    return x`
      <div id="typo3-pagetree" class="tree">
      ${m2(this.renderTree(), "")}
      </div>
    `;
  }
  getConfiguration() {
    if (this.configuration !== null) {
      return Promise.resolve(this.configuration);
    }
    const configurationUrl = top.TYPO3.settings.ajaxUrls.xima_categorytree_configuration;
    return new AjaxRequest(configurationUrl).get().then(async (response) => {
      const configuration = await response.resolve("json");
      this.configuration = configuration;
      this.mountPointPath = configuration.temporaryMountPoint || null;
      return configuration;
    });
  }
  async renderTree() {
    const configuration = await this.getConfiguration();
    return x`
      <typo3-backend-navigation-component-categorytree-toolbar id="typo3-pagetree-toolbar" .tree="${this.tree}"></typo3-backend-navigation-component-categorytree-toolbar>
      <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
        <typo3-backend-navigation-component-categorytree-tree
            id="typo3-pagetree-tree"
            class="tree-wrapper"
            .setup=${configuration}
            @typo3:tree:node-selected=${this.loadContent}
            @typo3:tree:node-context=${this.showContextMenu}
            @typo3:tree:nodes-prepared=${this.selectActiveNodeInLoadedNodes}
        ></typo3-backend-navigation-component-categorytree-tree>
      </div>
    `;
  }
  unsetTemporaryMountPoint() {
    Persistent.unset("pageTree_temporaryMountPoint").then(() => {
      this.mountPointPath = null;
    });
  }
  renderMountPoint() {
    if (this.mountPointPath === null) {
      return E;
    }
    return x`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${() => this.unsetTemporaryMountPoint()}" title="${lll("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `;
  }
  setTemporaryMountPoint(pid) {
    new AjaxRequest(this.configuration.setTemporaryMountPointUrl).post("pid=" + pid, {
      headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" }
    }).then((response) => response.resolve()).then((response) => {
      if (response && response.hasErrors) {
        this.tree.errorNotification(response.message);
        this.tree.loadData();
      } else {
        this.mountPointPath = response.mountPointPath;
      }
    }).catch((error) => {
      this.tree.errorNotification(error);
      this.tree.loadData();
    });
  }
};
_init2 = __decoratorStart(_a2);
PageTreeNavigationComponent = __decorateElement(_init2, 0, "PageTreeNavigationComponent", _PageTreeNavigationComponent_decorators, PageTreeNavigationComponent);
__runInitializers(_init2, 1, PageTreeNavigationComponent);
var _PageTreeToolbar_decorators, _init3, _a3;
_PageTreeToolbar_decorators = [t3("typo3-backend-navigation-component-categorytree-toolbar")];
var PageTreeToolbar = class extends (_a3 = TreeToolbar) {
  constructor() {
    super(...arguments);
    this.tree = null;
  }
  render() {
    return x`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${lll("labels.label.searchString")}
              </label>
              <input type="search" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll("tree.searchTermInfo")}">
          </div>
        </div>
        <div class="tree-toolbar__submenu">
          ${this.tree?.settings?.doktypes?.length ? this.tree.settings.doktypes.map((item) => {
      return x`
                <div
                  class="tree-toolbar__menuitem tree-toolbar__drag-node"
                  title="${item.title}"
                  draggable="true"
                  data-tree-icon="${item.icon}"
                  data-node-type="${item.nodeType}"
                  aria-hidden="true"
                  @dragstart="${(event) => {
        this.handleDragStart(event, item);
      }}"
                >
                  <typo3-backend-icon identifier="${item.icon}" size="small"></typo3-backend-icon>
                </div>
              `;
    }) : ""}
          <button
            type="button"
            class="tree-toolbar__menuitem dropdown-toggle dropdown-toggle-no-chevron float-end"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="${lll("labels.openPageTreeOptionsMenu")}"
          >
            <typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${() => this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.refresh")}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${(evt) => this.collapseAll(evt)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.collapse")}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `;
  }
  handleDragStart(event, item) {
    const newNode = {
      __hidden: false,
      __expanded: false,
      __indeterminate: false,
      __loading: false,
      __processed: false,
      __treeDragAction: "",
      __treeIdentifier: "",
      __treeParents: [""],
      __parents: [""],
      __x: 0,
      __y: 0,
      deletable: false,
      depth: 0,
      editable: true,
      hasChildren: false,
      icon: item.icon,
      overlayIcon: "",
      identifier: "NEW" + Math.floor(Math.random() * 1e9).toString(16),
      loaded: false,
      name: "",
      note: "",
      parentIdentifier: "",
      prefix: "",
      recordType: "pages",
      suffix: "",
      tooltip: "",
      type: "PageTreeItem",
      doktype: item.nodeType,
      statusInformation: [],
      labels: []
    };
    this.tree.draggingNode = newNode;
    this.tree.nodeDragMode = TreeNodeCommandEnum.NEW;
    event.dataTransfer.clearData();
    const metadata = {
      statusIconIdentifier: this.tree.getNodeDragStatusIcon(),
      tooltipIconIdentifier: item.icon,
      tooltipLabel: item.title
    };
    event.dataTransfer.setData(DataTransferTypes.dragTooltip, JSON.stringify(metadata));
    event.dataTransfer.setData(DataTransferTypes.newTreenode, JSON.stringify(newNode));
    event.dataTransfer.effectAllowed = "move";
  }
};
_init3 = __decoratorStart(_a3);
PageTreeToolbar = __decorateElement(_init3, 0, "PageTreeToolbar", _PageTreeToolbar_decorators, PageTreeToolbar);
__runInitializers(_init3, 1, PageTreeToolbar);
export {
  EditablePageTree,
  PageTreeNavigationComponent,
  navigationComponentName
};
/*! Bundled license information:

@lit/reactive-element/css-tag.js:
  (**
   * @license
   * Copyright 2019 Google LLC
   * SPDX-License-Identifier: BSD-3-Clause
   *)

@lit/reactive-element/reactive-element.js:
lit-html/lit-html.js:
lit-element/lit-element.js:
@lit/reactive-element/decorators/custom-element.js:
@lit/reactive-element/decorators/property.js:
@lit/reactive-element/decorators/state.js:
@lit/reactive-element/decorators/event-options.js:
@lit/reactive-element/decorators/base.js:
@lit/reactive-element/decorators/query.js:
@lit/reactive-element/decorators/query-all.js:
@lit/reactive-element/decorators/query-async.js:
@lit/reactive-element/decorators/query-assigned-nodes.js:
lit-html/directive.js:
lit-html/async-directive.js:
lit-html/directives/until.js:
  (**
   * @license
   * Copyright 2017 Google LLC
   * SPDX-License-Identifier: BSD-3-Clause
   *)

lit-html/is-server.js:
  (**
   * @license
   * Copyright 2022 Google LLC
   * SPDX-License-Identifier: BSD-3-Clause
   *)

@lit/reactive-element/decorators/query-assigned-elements.js:
lit-html/directives/private-async-helpers.js:
  (**
   * @license
   * Copyright 2021 Google LLC
   * SPDX-License-Identifier: BSD-3-Clause
   *)

lit-html/directive-helpers.js:
  (**
   * @license
   * Copyright 2020 Google LLC
   * SPDX-License-Identifier: BSD-3-Clause
   *)
*/
