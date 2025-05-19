from flask_wtf import FlaskForm
from flask_wtf.file import FileField, FileAllowed
from wtforms import StringField, PasswordField, BooleanField, SubmitField, TextAreaField, FloatField, IntegerField, SelectField
from wtforms.validators import DataRequired, Email, EqualTo, Length, ValidationError, URL, Optional, NumberRange
from models import User

class LoginForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    password = PasswordField('Senha', validators=[DataRequired()])
    remember_me = BooleanField('Lembrar de mim')
    submit = SubmitField('Entrar')

class RegistrationForm(FlaskForm):
    username = StringField('Nome de usuário', validators=[DataRequired(), Length(min=3, max=50)])
    email = StringField('Email', validators=[DataRequired(), Email()])
    password = PasswordField('Senha', validators=[DataRequired(), Length(min=6)])
    password2 = PasswordField('Confirmar senha', validators=[DataRequired(), EqualTo('password')])
    submit = SubmitField('Registrar')

    def validate_username(self, username):
        user = User.query.filter_by(username=username.data).first()
        if user is not None:
            raise ValidationError('Por favor use um nome de usuário diferente.')

    def validate_email(self, email):
        user = User.query.filter_by(email=email.data).first()
        if user is not None:
            raise ValidationError('Por favor use um endereço de email diferente.')

class ProductForm(FlaskForm):
    title = StringField('Título', validators=[DataRequired(), Length(min=3, max=200)])
    description = TextAreaField('Descrição', validators=[DataRequired()])
    price = FloatField('Preço', validators=[DataRequired(), NumberRange(min=0.01)])
    category_id = SelectField('Categoria', coerce=int, validators=[DataRequired()])
    image = FileField('Imagem do produto', validators=[FileAllowed(['jpg', 'png', 'jpeg'], 'Apenas imagens são permitidas!')])
    external_link = StringField('Link externo', validators=[Optional(), URL()])
    submit = SubmitField('Salvar')

class CategoryForm(FlaskForm):
    name = StringField('Nome da categoria', validators=[DataRequired(), Length(min=2, max=100)])
    submit = SubmitField('Salvar')

class MessageForm(FlaskForm):
    content = TextAreaField('Mensagem', validators=[DataRequired()])
    submit = SubmitField('Enviar')

class OrderPaymentNotificationForm(FlaskForm):
    submit = SubmitField('Confirmar Pagamento') 